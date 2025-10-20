#!/bin/bash

# Wait for mail server to be ready
echo "Waiting for mail server to start..."
sleep 10

# Add email accounts
docker exec -it roundcube_mail setup email add aa@aa.aa password
docker exec -it roundcube_mail setup email add bb@aa.aa password

echo "Email accounts created:"
echo "  aa@aa.aa / password"
echo "  bb@aa.aa / password"
