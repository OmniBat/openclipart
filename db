#!/bin/bash
# Run mysql console with data from config.json

$(python -c 'import json; c=json.load(open("config.json"));print "mysql -u%s -p%s %s" % (c["db_user"], c["db_pass"], c["db_name"])')
