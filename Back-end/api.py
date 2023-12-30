from flask import Flask, jsonify
import paho.mqtt.client as mqtt
import json

app = Flask(__name__)

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
            # Check if payload is not empty before parsing as JSON
            if payload:
                data = json.loads(payload)
                print(f"Parsed JSON data: {data}")

                self.latest_data[topic] = data
        except json.JSONDecodeError as e:
            print(f"Error decoding JSON: {e}")

        print(f"Latest data: {self.latest_data}")
        

mqtt_broker_address = "broker.hivemq.com"
mqtt_port = 1883
mqtt_topics = ["debit", "penggunaanLiter", "jarak", "kapasitas", "waterlevel", "suhu", "statuspompa"]

# Create an instance of MQTTHandler
mqtt_handler = MQTTHandler(mqtt_broker_address, mqtt_port, mqtt_topics)

# Define API endpoints for each MQTT topic
for topic in mqtt_topics:
    endpoint_name = f"/read_latest_data/{topic}"

    def read_latest_data_topic_specific(topic=topic):
        return jsonify({topic: mqtt_handler.latest_data[topic]})

    app.add_url_rule(endpoint_name, f"read_latest_data_{topic}", read_latest_data_topic_specific)
    


if __name__ == '__main__':
    app.run(debug=True)
