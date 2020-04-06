# mongowrapper
This is a proof of concept to handle MongoDB with PHP and Python to handle a dynamic collection of data from a legacy system

# Install and run MongoDB enterprise server
https://docs.mongodb.com/manual/tutorial/install-mongodb-enterprise-on-os-x/

Run locally:
$ mongod --dbpath /usr/local/var/mongodb --logpath /usr/local/var/log/mongodb/mongo.log --fork --auth --bind_ip=192.168.2.104

# Install MongoDB dependencies (Ubuntu)
- sudo add-apt-repository ppa:ondrej/php
- sudo apt-get update
- sudo apt-get install php7.0-dev --fix-missing
- sudo pecl install mongodb
- composer require mongodb/mongodb --ignore-platform-reqs

OR only - sudo apt update && sudo apt install php-mongodb

# Create a user on MongoDB
db.createUser(
  {
    user: "ipuser",
    pwd: "123",
    roles: [ { role: "userAdminAnyDatabase", db: "admin" }, "readWriteAnyDatabase" ]
  }
)
- Connect using: $ mongo --authenticationDatabase "admin" -u "ipuser" -p "123"