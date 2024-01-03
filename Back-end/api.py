from flask import Flask, jsonify
from flask_cors import CORS
import paho.mqtt.client as mqtt
import json
import time

app = Flask(__name__)
CORS(app)

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
        global pump_start_time  # Declare pump_start_time as global

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

@app.route('/read_all_latest_data')
def read_all_latest_data():
    all_data = {topic: mqtt_handler.latest_data[topic] for topic in mqtt_topics}
    return jsonify(all_data)

        

mqtt_broker_address = "broker.hivemq.com"
mqtt_port = 1883
mqtt_topics = ["ione/debit", "ione/penggunaanLiter", "ione/jarak", "ione/kapasitas", "ione/waterlevel", "ione/suhu", "ione/statuspompa", "ione/volumeAir"]

# Create an instance of MQTTHandler
mqtt_handler = MQTTHandler(mqtt_broker_address, mqtt_port, mqtt_topics)

# Define API endpoints for each MQTT topic
for topic in mqtt_topics:
    endpoint_name = f"/read_latest_data/{topic}"

    def read_latest_data_topic_specific(topic=topic):
        return jsonify({topic: mqtt_handler.latest_data[topic]})

    app.add_url_rule(endpoint_name, f"read_latest_data_{topic}", read_latest_data_topic_specific)
    


if __name__ == '__main__':
    app.run(host="0.0.0.0", port=int("3000"), debug=True)
