from flask import Flask, jsonify
from flask_cors import CORS
import paho.mqtt.client as mqtt
import json
from flask_apscheduler import APScheduler
import mysql.connector
import threading

app = Flask(__name__)
CORS(app)

database_lock = threading.Lock()

def calculate_average(attribute):
    connection = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='iotbb'
    )
    cursor = connection.cursor()

    cursor.execute(f'SELECT AVG({attribute}) FROM infotank')
    result = cursor.fetchone()[0]

    connection.close()
    return result

def calculate_average_all():
    connection = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='iotbb'
    )
    cursor = connection.cursor()

    averages = {}
    attributes = ['debit_air', 'penggunaanLiter', 'kapasitas', 'kedalaman_air', 'suhu_air', 'stat_pompa']

    for attribute in attributes:
        cursor.execute(f'SELECT AVG({attribute}) FROM infotank')
        averages[attribute] = cursor.fetchone()[0]

    connection.close()
    return averages

# Define API endpoints for each attribute
attributes = ['debit_air', 'penggunaanLiter', 'kapasitas', 'kedalaman_air', 'suhu_air', 'stat_pompa']

# Create a dictionary to map attribute names to route functions
route_functions = {}

# Define route functions
def create_route_function(attribute):
    def route_function():
        with database_lock:
            average_value = calculate_average(attribute)
        return jsonify({f'average_{attribute}': average_value})

    return route_function

# Associate route functions with attribute names
for attribute in attributes:
    route_functions[attribute] = create_route_function(attribute)

# Use a loop to define routes
for attribute, route_function in route_functions.items():
    app.add_url_rule(f'/average_{attribute}', f'average_{attribute}', route_function)

# Define a route function for the overall average
def average_all():
    with database_lock:
        average_values = calculate_average_all()
    return jsonify(average_values)

# Associate the overall average route function with the route path
app.add_url_rule('/average_all', 'average_all', average_all)



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

# def save_data_to_table(table_name, data):
#     global mqtt_handler
#     connection = mysql.connector.connect(
#         host='localhost',
#         user='root',
#         password='',
#         database='iotbb'
#     )
#     cursor = connection.cursor()

#     try:
#         with mqtt_handler_lock:
#             cursor.execute(f"SHOW TABLES LIKE '{table_name}'")
#             table_exists = cursor.fetchone() is not None

#             if table_exists:
#                 for topic, data_item in data.items():
#                     if data_item is not None and isinstance(data_item, dict):
#                         # Flatten the nested structure
#                         flattened_data = flatten_dict(data_item)

#                         # Extract column names from the flattened dictionary keys
#                         columns = ', '.join(flattened_data.keys())

#                         # Generate placeholders for the values in the SQL statement
#                         placeholders = ', '.join(['%s'] * len(flattened_data))

#                         # Generate the SQL statement with dynamic column names
#                         sql_statement = f"INSERT INTO {table_name} ({columns}, timestamp) VALUES ({placeholders}, NOW())"

#                         # Extract values from the flattened dictionary
#                         values = tuple(flattened_data.values())

#                         # Execute the SQL statement with parameters
#                         cursor.execute(sql_statement, values)

#                 print("Data successfully saved to the database!")

#         connection.commit()
#     except Exception as e:
#         print(f"Error saving data to the database: {e}")
#     finally:
#         connection.close()

# def flatten_dict(d, parent_key='', sep='/'):
#     items = []
#     for k, v in d.items():
#         new_key = f"{parent_key}{sep}{k}" if parent_key else k
#         if isinstance(v, dict):
#             items.extend(flatten_dict(v, new_key, sep=sep).items())
#         else:
#             items.append((new_key, v))
#     return dict(items)


# # Example usage
# def save_latest_data_to_db():
#     save_data_to_table('infotank', mqtt_handler.latest_data)

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

@app.route('/read_all_latest_data')
def read_all_latest_data():
    with mqtt_handler_lock:
        all_data = {topic: mqtt_handler.latest_data[topic] for topic in mqtt_topics}
    return jsonify(all_data)

# Schedule saving latest data to the database every 1 minute using Flask-APScheduler
# scheduler = APScheduler()
# scheduler.init_app(app)
# scheduler.start()
# scheduler.add_job(id='save_data_to_db', func=save_latest_data_to_db, trigger='interval', seconds=60)



if __name__ == '__main__':
    app.run(host="0.0.0.0", port=int("3000"), debug=True)
