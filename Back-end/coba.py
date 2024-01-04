from flask import Flask, jsonify
from flask_cors import CORS
import paho.mqtt.client as mqtt
import json
from flask_apscheduler import APScheduler
import mysql.connector
import threading

app = Flask(__name__)
CORS(app)

# Use a lock to synchronize access to the MQTTHandler instance
mqtt_handler_lock = threading.Lock()

class MQTTHandler:
    def __init__(self, broker_address, broker_port, topics):
        self.latest_data = {topic: None for topic in topics}
        self.mqtt_client = mqtt.Client()
        self.topics = topics

        self.setup_mqtt(broker_address, broker_port)

    def setup_mqtt(self, broker_address, broker_port):
        self.mqtt_client.on_message = self.on_message
        self.mqtt_client.connect(broker_address, broker_port, 60)
        self.mqtt_client.loop_start()

        for topic in self.topics:
            self.mqtt_client.subscribe(topic)

    def on_message(self, client, userdata, msg):
        payload = msg.payload.decode('utf-8')
        topic = msg.topic

        print(f"Received raw message on topic {topic}: {payload}")

        try:
            if payload:
                data = json.loads(payload)
                print(f"Parsed JSON data: {data}")

                self.latest_data[topic] = data
        except json.JSONDecodeError as e:
            print(f"Error decoding JSON: {e}")

        print(f"Latest data: {self.latest_data}")

def save_latest_data_to_db():
    global mqtt_handler
    connection = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='iotbb'
    )
    cursor = connection.cursor()

    with mqtt_handler_lock:
        cursor.execute("SHOW TABLES LIKE 'infotank'")
        table_exists = cursor.fetchone() is not None

        if table_exists:
            for topic, data in mqtt_handler.latest_data.items():
                if data is not None:
                    if isinstance(data, dict):
                        debit_air = data.get('debit', 0.0)
                        penggunaan_liter = data.get('penggunaanLiter', 0)
                        kapasitas = data.get('kapasitas', 0)
                        kedalaman_air = data.get('kedalaman_air', 0)
                        suhu_air = data.get('suhu_air', 0)
                        stat_pompa = data.get('stat_pompa', 0)
                    else:
                        # Handle the case where data is not a dictionary
                        # You might want to assign default values or handle it differently
                        debit_air = 0.0
                        penggunaan_liter = 0
                        kapasitas = 0
                        kedalaman_air = 0
                        suhu_air = 0
                        stat_pompa = 0

                    cursor.execute('INSERT INTO infotank (topic, debit_air, penggunaanLiter, kapasitas, kedalaman_air, suhu_air, stat_pompa, timestamp) VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())',
                                   (topic, debit_air, penggunaan_liter, kapasitas, kedalaman_air, suhu_air, stat_pompa))

            print("Data successfully saved to the database!")

    connection.commit()
    connection.close()







@app.route('/read_all_latest_data')
def read_all_latest_data():
    with mqtt_handler_lock:
        all_data = {topic: mqtt_handler.latest_data[topic] for topic in mqtt_topics}
    return jsonify(all_data)

@app.route('/average_debit_air')
def average_debit_air():
    connection = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='iotbb'
    )
    cursor = connection.cursor()

    cursor.execute('SELECT AVG(debit_air) FROM infotank')

    result = cursor.fetchone()[0]

    connection.close()

    return jsonify({"average_debit_air": result})

mqtt_broker_address = "broker.hivemq.com"
mqtt_port = 1883
mqtt_topics = ["ione/debit", "ione/penggunaanLiter", "ione/jarak", "ione/kapasitas", "ione/waterlevel", "ione/suhu", "ione/statuspompa", "ione/volumeAir"]

# Create an instance of MQTTHandler
mqtt_handler = MQTTHandler(mqtt_broker_address, mqtt_port, mqtt_topics)

# Define API endpoints for each MQTT topic
for topic in mqtt_topics:
    endpoint_name = f"/read_latest_data/{topic}"

    def read_latest_data_topic_specific(topic=topic):
        with mqtt_handler_lock:
            return jsonify({topic: mqtt_handler.latest_data[topic]})

    app.add_url_rule(endpoint_name, f"read_latest_data_{topic}", read_latest_data_topic_specific)

# Schedule saving latest data to the database every 1 minute using Flask-APScheduler
scheduler = APScheduler()
scheduler.init_app(app)
scheduler.start()
scheduler.add_job(id='save_data_to_db', func=save_latest_data_to_db, trigger='interval', seconds=60)

if __name__ == '__main__':
    app.run(host="0.0.0.0", port=int("5000"), debug=True)
