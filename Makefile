LESS = $(shell find styles -name "*.less" -type f | sort)
COMPONENT = $(shell find client -name "*.js" -type f | sort)

less: $(LESS) 
	lessc styles/main.less > styles/main.css

components: $(COMPONENT) 
	cd client; component build --name=main
	mv client/build/main.js public/js/main.js
	mv client/build/main.css public/css/main.css


minify: public/js/main.js
	uglifyjs public/js/main.js --output=public/js/main.min.js



clean:
	rm public/css/main.css

.PHONY: clean