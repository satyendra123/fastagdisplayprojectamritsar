follow these lines for making the windows services
step-1 open the cmd and write- cd C:\Parking Management System - Houston Systems\Services\BoomService\EntryBoompython
step-2 so humara cmd ab is directory me open ho jayega aur hum ye command chalayenge exe banane ke liye- pyinstaller --onefile EntryBarrier.pyinstaller
step-3 ab jo dist folder me jayenge aur waha se exe copy karke jaha apna sara file hai yaha par paste kar denge


Note- jab client ko denge to hum code nahi balki direct exe denge aur ye config.txt file denge client jo hai apne hisab se changes kar lega is file me aur exe ko run kar dega
so hume har bar files ke liye exe banane ki jarurat nahi hai

step-4 is links me jakar NSSM ko download karle aur iske exe ko environment me path add kar denge exe ka https://nagasudhir.blogspot.com/2022/09/run-python-flask-server-as-windows.html
step-5 cmd ko open karenge administrator me aur waha jayenge jaha humara exe files hai cd C:\Parking Management System - Houston Systems\Services\BoomService\EntryBoompython.
        aur phir jab humara cmd is files and folder me aa jayega to ye command chalayenge services banane ke liye-  nssm.exe install HousysEntryBarrier "%cd%\EntryBarrier.exe"

import socket
import datetime
import time
import mysql.connector
import threading

SERVER_IP = '192.168.1.157'
SERVER_PORT = 7000
INITIAL_MESSAGE = "|ENTRY%"
LOG_FILE = "exit_connection_log.txt"

# MySQL database configuration
db_config = {'host': '192.168.40.100', 'user': 'root', 'password': '', 'database': 'paytm_park'}

def log_message(message):
    with open(LOG_FILE, 'a') as log:
        timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        log.write(f"{timestamp}: {message}\n")

def send_initial_message(client_socket):
    try:
        client_socket.sendall(INITIAL_MESSAGE.encode())
        log_message(f"Sent initial message: {INITIAL_MESSAGE}")
        
    except Exception as e:
        log_message(f"Failed to send initial message: {e}")

def check_exit_boom(client_socket):
    while True:
        try:
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()

            query = "SELECT exitboom FROM boomsig1"
            cursor.execute(query)

            result = cursor.fetchone()
            if result:
                exit_boom_status = result[0]
                if exit_boom_status == 'Y':
                    log_message("Exit boom is open")
                    send_initial_message(client_socket)
                    time.sleep(3)
                    cursor.execute("UPDATE boomsig1 SET exitboom = 'N'")
                    conn.commit()
                    log_message("Exit boom status reset to 'N' after 3 seconds")

            cursor.close()
            conn.close()

        except mysql.connector.Error as err:
            log_message(f"Error: {err}")
        
        time.sleep(1)  # Adjust the sleep duration as needed to control the check frequency

def main():
    client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

    try:
        client_socket.connect((SERVER_IP, SERVER_PORT))
        log_message(f"Connected to {SERVER_IP}:{SERVER_PORT}")

        # Start the exit boom check in a separate thread
        thread = threading.Thread(target=check_exit_boom, args=(client_socket,))
        thread.daemon = True
        thread.start()

        while True:
            data = client_socket.recv(1024)
            if data:
                decoded_data = data.decode('utf-8')
                if "|HLT%" in decoded_data:
                    log_message(f"Received health packet: {decoded_data}")

    except ConnectionRefusedError:
        log_message(f"Connection to {SERVER_IP}:{SERVER_PORT} was refused.")
    except TimeoutError:
        log_message("Connection timed out. Check IP and port.")
    except Exception as e:
        log_message(f"An error occurred: {e}")

    finally:
        client_socket.close()

if __name__ == "__main__":
    main()
