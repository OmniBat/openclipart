SHELL = /bin/bash

LESS = $(shell find styles -name "*.less" -type f | sort)
COMPONENT = $(shell find client -name "*.js" -type f | sort)

# ----- Rules -----------------------------------------------------------------
.PHONY:		clean schema drop migration

less: $(LESS)
	lessc styles/main.less > public/css/main.css || rm -f public/css/main.css

components: $(COMPONENT) 
	cd client; component build --name=main
	mv client/build/main.js public/js/main.js
	mv client/build/main.css public/css/main.css

minify: public/js/main.js
	uglifyjs public/js/main.js --output=public/js/main.min.js

schema:
	mysql -D ocal < resources/scripts/sql/schema.sql

drop:
	mysql -D ocal < resources/scripts/sql/drop.sql

migrate:
	mysql -D ocal < resources/scripts/sql/migration.sql


clean:
	rm public/css/main.css
