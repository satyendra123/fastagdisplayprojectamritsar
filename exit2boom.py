follow these lines for making the windows services
step-1 open the cmd and write- cd C:\Parking Management System - Houston Systems\Services\BoomService\EntryBoompython
step-2 so humara cmd ab is directory me open ho jayega aur hum ye command chalayenge exe banane ke liye- pyinstaller --onefile EntryBarrier.pyinstaller
step-3 ab jo dist folder me jayenge aur waha se exe copy karke jaha apna sara file hai yaha par paste kar denge


Note- jab client ko denge to hum code nahi balki direct exe denge aur ye config.txt file denge client jo hai apne hisab se changes kar lega is file me aur exe ko run kar dega
so hume har bar files ke liye exe banane ki jarurat nahi hai

step-4 is links me jakar NSSM ko download karle aur iske exe ko environment me path add kar denge exe ka https://nagasudhir.blogspot.com/2022/09/run-python-flask-server-as-windows.html
step-5 cmd ko open karenge administrator me aur waha jayenge jaha humara exe files hai cd C:\Parking Management System - Houston Systems\Services\BoomService\EntryBoompython.
        aur phir jab humara cmd is files and folder me aa jayega to ye command chalayenge services banane ke liye-  nssm.exe install HousysEntryBarrier "%cd%\EntryBarrier.exe"

DbPath=server=127.0.0.1;user=root;password=;database=paytm_park;
Server_IP=192.168.1.151
Port=7000
Retry_milisec=30000
DebugLog=True
TimerMilliSec=5000

step-2 ye humara EntryBarrier.py wala code hai
import socket
import datetime
import time
import mysql.connector
import threading

CONFIG_FILE = "config.txt"
LOG_FILE = "Exit_connection.txt"

def read_config():
    config = {}
    with open(CONFIG_FILE, 'r') as file:
        for line in file:
            line = line.strip()
            if "=" in line:
                key, value = line.split("=", 1)
                config[key] = value
    return config

def parse_db_config(db_path):
    db_config = {}
    items = db_path.split(";")
    for item in items:
        if "=" in item:
            key, value = item.split("=")
            db_config[key] = value
    return db_config

def log_message(message, debug_mode=True):
    if not debug_mode:
        return

    with open(LOG_FILE, 'a') as log:
        timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        log.write(f"{timestamp}: {message}\n")

def send_initial_message(client_socket):
    try:
        client_socket.sendall(INITIAL_MESSAGE.encode())
        log_message(f"Sent initial message: {INITIAL_MESSAGE}")
    except Exception as e:
        log_message(f"Failed to send initial message: {e}")

def check_exit_boom(client_socket, db_conn, timer_millisec):
    try:
        cursor = db_conn.cursor()
        while True:
            cursor.execute("SELECT exitboom FROM boomsig1")
            result = cursor.fetchone()

            if result:
                exit_boom_status = result[0]
                if exit_boom_status == 'Y':
                    log_message("Exit boom is open")
                    send_initial_message(client_socket)
                    time.sleep(3)
                    
                    # Update the exitboom status back to 'N' after the message is sent
                    cursor.execute("UPDATE boomsig1 SET exitboom = 'N'")
                    db_conn.commit()
                    log_message("Exit boom status reset to 'N' after 3 seconds")

            time.sleep(timer_millisec / 1000)  # Sleep for the timer duration
    except mysql.connector.Error as err:
        log_message(f"Database error: {err}")
    finally:
        cursor.close()

def main():
    config = read_config()

    SERVER_IP = config['Server_IP']
    SERVER_PORT = int(config['Port'])
    DB_PATH = config['DbPath']
    RETRY_MILLISECONDS = int(config['Retry_milisec'])
    TIMER_MILLISECONDS = int(config['TimerMilliSec'])
    DEBUG_LOG = config['DebugLog'] == 'True'
    
    db_config = parse_db_config(DB_PATH)

    client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

    try:
        client_socket.connect((SERVER_IP, SERVER_PORT))
        log_message(f"Connected to {SERVER_IP}:{SERVER_PORT}", DEBUG_LOG)
        
        db_conn = mysql.connector.connect(**db_config)
        log_message("Database connection established in advance.", DEBUG_LOG)
        
        thread = threading.Thread(target=check_exit_boom, args=(client_socket, db_conn, TIMER_MILLISECONDS))
        thread.daemon = True
        thread.start()
        
        while True:
            data = client_socket.recv(1024)
            if data:
                decoded_data = data.decode('utf-8')
                if "|HLT%" in decoded_data:
                    log_message(f"Received health packet: {decoded_data}", DEBUG_LOG)

    except ConnectionRefusedError:
        log_message(f"Connection to {SERVER_IP}:{SERVER_PORT} was refused.", DEBUG_LOG)
    except TimeoutError:
        log_message("Connection timed out. Check IP and port.", DEBUG_LOG)
    except Exception as e:
        log_message(f"An error occurred: {e}", DEBUG_LOG)
    finally:
        client_socket.close()
        db_conn.close()

if __name__ == "__main__":
    main()
