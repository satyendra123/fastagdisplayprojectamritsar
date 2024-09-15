from flask import Flask, request, Response
import OpenSSL.crypto as crypto
import xml.etree.ElementTree as ET
from lxml import etree
from signxml import XMLSigner, XMLVerifier
from datetime import datetime
import pymysql


app = Flask(__name__)


def sign_xml(data_to_sign):
    cert = open("domain.crt").read()
    key = open("domain.pem", "rb").read()
    root = etree.fromstring(data_to_sign)
    signed_root = XMLSigner().sign(root, key=key, cert=cert)
    verified_data = XMLVerifier().verify(signed_root, x509_cert=cert).signed_xml
    tree = etree.ElementTree(signed_root)
    file_path = f"requests/request_signed_xml_{(datetime.now()).strftime('%Y-%m-%d_%H-%M-%S-%f')}.xml"
   # import pdb; pdb.set_trace(); 
    tree.write(file_path)
    return file_path

@app.route('/sign-xml', methods=['POST'])
def sign_xml_endpoint():
    signed_xml = sign_xml(request.data)

    # Return the signed XML content
    response = Response(signed_xml, content_type='application/xml')
    return response


@app.route('/update-database', methods=['POST'])
def update_database_endpoint():
    request_data = request.get_json()
    veh_no = request_data.get('car_number', 'XXXXXXXXXX')
    charges = request_data.get('tarif_amount', 0)
    token = request_data.get('token', '')

    if charges > 1500:
        charges = 0

    display_id = 0

    if token == "SE9VU1lTLVBBUksyMDIzUFRN":
        display_id = 1
    elif token == "SE9VU1lTLVBBUksyMDIzUFRN1":
        display_id = 2

    conn = pymysql.connect(host="192.168.40.100", user="root", password="", database="paytm_park")
    x = conn.cursor()
    sql = """UPDATE display_response SET car_number = '%s', tarif_amount = %s, shown_on_screen = %s
         WHERE display_id=%s""" % (veh_no, charges, 1, display_id)
    x.execute(sql)
    conn.commit()
    return {"message": "database update done", "status": "OK"}

if __name__ == '__main__':
    app.run()


"""
CREATE TABLE display_response (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_number VARCHAR(50) not null,
    tarif_amount DECIMAL(10, 2) not null,
    display_id integer,
    shown_on_screen TINYINT(1) not null DEFAULT 1
);
insert into display_response (car_number, tarif_amount, display_id, shown_on_screen) values ('XXX', 120, 1, 1);
insert into display_response (car_number, tarif_amount, display_id, shown_on_screen) values ('XXX', 120, 2, 1);
"""
