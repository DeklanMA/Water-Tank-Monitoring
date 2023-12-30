//IONE SMART RADAR WATER TANK MONITORING CODE BY DHNZ
#include "PubSubClient.h"
#include "WiFi.h"
#include <OneWire.h>
#include <DallasTemperature.h>

WiFiClient wifiClient; // Membuat objek wifiClient
PubSubClient mqtt(wifiClient); // Membuat objek mqttClient dengan konstruktor objek WiFiClient 

// setting wifi, SSID WIFI dan Pass
// const char* ssid = "DHANZ";
// const char* pass = "pilarianmamah24";

const char* ssid = "pona";
const char* pass = "dhanz2423";

//Setting tinggi toren, tinggi sensor, dan diameter
int sensorHeight = 70;   //cm
int tankHeight = 50;   //cm

//sensor ultrasonik JSN-SR04
#define ECHOPIN 26 // attach pin GPIO18 to pin Echo of JSN-SR04
#define TRIGPIN 27  // attach pin GPIO5 ESP32 to pin Trig of JSN-SR04     
//Water Flow sensor
#define flowsensor 13 //sensor water flow berada pada pin 18 esp32
//Led Indicator
#define GREEN_LED 5
#define YELLOW_LED 4
#define RED_LED 2
//Relay
#define RELAY 19
//valve
#define VALVE 20

//sensorflow cai
long currentMillis = 0;
long previousMillis = 0;
int interval = 2000; //interval waktu setiap 2 dedtik milsec untuk memproses data
boolean ledState = LOW;
float calibrationFactor = 4.5; //konstanta waterflow sensor
volatile byte pulseCount;
byte pulse1Sec = 0;
float debit;
unsigned int flowMilliLitres;
unsigned long totalMilliLitres;
unsigned long totalLitres;
void IRAM_ATTR pulseCounter()
{
  pulseCount++;
}

//variabel sensor waterflow
long duration_us; 
float distance_cm, waterLevel, capacity;

//sensor suhu air DS18B20
// Inisialisasi pin data untuk sensor DS18B20
const int oneWireBusPin = 4;  // Sesuaikan dengan pin yang digunakan pada Arduino Anda
float celsius;
// Inisialisasi objek OneWire dan DallasTemperature
OneWire oneWire(oneWireBusPin);
DallasTemperature sensors(&oneWire);

//pembuatan variabel untuk status pompa
char statpompa[6];

//kebutuhan mqtt
const char* subs_pompa = "asharilabs/lampu";
const char* subs_valve = "asharilabs/servo";

const char* pub_debit = "asharilabs/humi";
const char* pub_penggunaanLiter = "asharilabs/humi";
const char* pub_jarak = "asharilabs/humi";
const char* pub_kapasitas = "asharilabs/humi";
const char* pub_waterlevel = "asharilabs/humi";
const char* pub_suhu = "asharilabs/suhu";
const char* pub_statsalat = "asharilabs/alat";

const char* broker = "broker.hivemq.com";
const int brokerPort = 1883;

String macAddr;
String ipAddr;

void WifiSetup(){
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, pass);
  while(WiFi.status() != WL_CONNECTED){
    Serial.print(".");
    delay(500);
  }

  ipAddr = WiFi.localIP().toString().c_str();
  macAddr = WiFi.macAddress();

  Serial.print("Connected at: ");
  Serial.println(ipAddr);
  // Serial.println(WiFi.localIP());
  Serial.print("MAC: ");
  Serial.println(macAddr);
}

void wifi_reconnect(){
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, pass);
  int i = 0;
  status_alat = 1; //0 = online
  while(WiFi.status() != WL_CONNECTED){
    Serial.print(".");
    i = i + 1;
    if (i == 15){
    status_alat = 0; //0 = offline
    Serial.println();
    Serial.println("Alat dalam keadaan Offline ");
    break;
    }
    delay(1000);
  }
}

void MqttSetup(){

  mqtt.setServer(broker, brokerPort);
  mqtt.setCallback(Callback);
}

void Reconnect(){
  Serial.println("Connecting to MQTT Broker...");
  while (!mqtt.connected()) {
      Serial.println("Reconnecting to MQTT Broker..");
      String clientId = "ESP32Client-";
      clientId += String(random(0xffff), HEX);
      
      if (mqtt.connect(clientId.c_str())) {
        Serial.print("ID: ");
        Serial.println(clientId);
        Serial.println("Connected.");
        mqtt.subscribe(subs_pompa);
        mqtt.subscribe(subs_valve);
      }      
      else {
        Serial.print("failed, rc=");
        Serial.print(mqtt.state());
        Serial.println(" try again in 5 seconds");
        delay(5000);
      }
  }
}

// Func untuk menerima pesan yang dikirimkan dari topik
void Callback(char* topic, byte* message, unsigned int length){

  Serial.print("Incoming topic: ");
  Serial.println(topic);
  
  String _messageStr;

  for (int i = 0; i < length; i++) {
    Serial.print((char)message[i]);
    _messageStr += (char)message[i];
  }

  Serial.println();

  if(String(topic) == subs_pompa){
    _messageStr.toLowerCase();
    Serial.print("Output to: ");

    if( _messageStr == "1"){
      Serial.println("Lampu ON");
     
    }
    else if( _messageStr == "0"){
      Serial.println("Lampu OFF");
      
    }
  }
  else if( String(topic) == subs_valve){

    int a = map(_messageStr.toInt(), 0, 100, 0, 180);
    
  }
}

void setup()
{
  pinMode(TRIGPIN, OUTPUT); // Sets the TRIGPIN as an OUTPUT
  pinMode(ECHOPIN, INPUT); // Sets the ECHOPIN as an INPUT

  pinMode(GREEN_LED, OUTPUT);
  pinMode(YELLOW_LED, OUTPUT);
  pinMode(RED_LED, OUTPUT);

  pinMode(RELAY, OUTPUT);
  pinMode(VALVE, OUTPUT);

  pinMode(flowsensor, INPUT_PULLUP);

  pulseCount = 0;
  debit = 0.0;
  flowMilliLitres = 0;
  totalMilliLitres = 0;
  previousMillis = 0;

  attachInterrupt(digitalPinToInterrupt(flowsensor), pulseCounter, FALLING);

  // Serial Communication is starting with 9600 of
  // baudrate speed
  Serial.begin(9600);

  Serial.println("Set Up WIFI");
  WifiSetup();

  MqttSetup();

  Serial.println("Setting Up IONE Smart Radar Water Tank Monitoring....");
  delay(1000);
  mqtt.publish(pub_statsalat, "Setting Up IONE Smart Radar Water Tank Monitoring....");
}
     
void loop(){

  if(WiFi.status() != WL_CONNECTED){
    wifi_reconnect();
  }
  else{
      if( !mqtt.connected()){
    Reconnect();
    } 
  mqtt.loop();
  }

  //menghitung debit air dan penggunaan air
  currentMillis = millis();
  if (currentMillis - previousMillis > interval) {
    
    pulse1Sec = pulseCount;
    pulseCount = 0;

    // Because this loop may not complete in exactly 1 second intervals we calculate
    // the number of milliseconds that have passed since the last execution and use
    // that to scale the output. We also apply the calibrationFactor to scale the output
    // based on the number of pulses per second per units of measure (litres/minute in
    // this case) coming from the sensor.
    debit = ((1000.0 / (millis() - previousMillis)) * pulse1Sec) / calibrationFactor;
    previousMillis = millis();

    // Divide the flow rate in litres/minute by 60 to determine how many litres have
    // passed through the sensor in this 1 second interval, then multiply by 1000 to
    // convert to millilitres.
    flowMilliLitres = (debit / 60) * 1000;

    // Add the millilitres passed in this second to the cumulative total
    totalMilliLitres += flowMilliLitres;
    
    // Print the flow rate for this second in litres / minute
    Serial.print("Flow rate: ");
    Serial.print(int(debit));  // Print the integer part of the variable
    Serial.print("L/min");
    Serial.print("\t");       // Print tab space

    // Print the cumulative total of litres flowed since starting
    Serial.print("Output Liquid Quantity: ");
    Serial.print(totalMilliLitres);
    Serial.print("mL / ");
    totalLitres = totalMilliLitres / 1000;
    Serial.print(totalLitres);
    Serial.println("L");

    //mendeteksi suhu 
    sensors.requestTemperatures();  // Minta sensor untuk membaca suhu
    // Baca suhu dalam Celsius dan Fahrenheit
    celsius = sensors.getTempCByIndex(0);

    //mendeteksi kedalaman air
    waterLevel = sensorHeight - distance(TRIGPIN, ECHOPIN);
    capacity = (waterLevel * 100)/tankHeight;

    Serial.print("Jarak: " + String(distance_cm) + "cm ");
    Serial.print("Capacity: " + String(capacity) + "%, ");
    Serial.print("Water Level: " + String(waterLevel) + "cm");
    Serial.println("Suhu Celsius: " + String(celsius) + "°C");
    
    //mqtt.publish(pub_distance, capacity);
     

    if (capacity < (-10) ){
      Serial.println("Sensor error, Periksa keadaan alat");
      strcpy (statpompa , "ERROR");
    } else if (capacity < 20) {
      digitalWrite(RELAY, HIGH);
      Serial.println("Kapasitas rendah, Pompa menyala");
      strcpy (statpompa , "ON");
    } else if (capacity >= 100) {
      digitalWrite(RELAY, LOW);
      Serial.println("Kapasitas penuh, Pompa mati");
      strcpy (statpompa , "OFF");
    } 
  }

  if(WiFi.status() == WL_CONNECTED){
    kirimdata();
  }

}

void kirimdata(){  //fungsi untuk mengirimkan semua data dari sensor ke MQTT
  
    char _debit[8]; //membuat tipe data char untuk variabel debit
    dtostrf(debit, 1, 2, _debit); //mengubah tipe data ke char agar bisa dikirimkan ke mqtt
    char _totalLitres[8];
    dtostrf(totalLitres, 1, 2, _totalLitres);
    char _jarak[8];
    dtostrf(distance_cm, 1, 2, _jarak);
    char _capacity[8];
    dtostrf(capacity, 1, 2, _capacity);
    char _waterLevel[8];
    dtostrf(waterLevel, 1, 2, _waterLevel);
    char _suhu[8];
    dtostrf(celsius, 1, 2, _suhu);

    //mempublish semua data sistem ke MQTT
    mqtt.publish(pub_debit, _debit);
    mqtt.publish(pub_penggunaanLiter, _totalLitres);
    mqtt.publish(pub_jarak, _jarak);
    mqtt.publish(pub_kapasitas, _capacity);
    mqtt.publish(pub_waterlevel, _waterLevel);
    mqtt.publish(pub_suhu, _suhu);
    mqtt.publish(pub_statsalat, statpompa);
}

float distance(int TRIG, int ECHO) {
  digitalWrite(TRIG, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG, LOW);

  duration_us = pulseIn(ECHO, HIGH);
  distance_cm = 0.017 * duration_us;
  return distance_cm;
}