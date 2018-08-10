#!/bin/bash
set -e

# Create a password protected key
echo "Create a password protected key:"
openssl genrsa -des3 -out devcert.pass.key 2048
echo ""
echo "Unpack the password protected key, repeat your password:"
openssl rsa -in devcert.pass.key -out devcert.key
echo ""
echo "Create a certificate signing request. Enter valid parameters and *.scoutswetteren.dev as Common Name!"
openssl req -nodes -new -key devcert.key -out devcert.csr
echo ""

echo "Now generate the certificate, valid for 10 years"
openssl x509 -req -sha256 -days 3650 -in devcert.csr -signkey devcert.key -out devcert.crt
echo ""
echo "Generated. Please trust the certificate manually"