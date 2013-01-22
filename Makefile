LESS = $(shell find styles -name "*.less" -type f | sort)
COMPONENT = $(shell find client -name "*.js" -type f | sort)

css: $(LESS) 
	lessc styles/main.less > public/css/main.css

js: $(COMPONENT)
	cd client; component build --out=../public/js/ --name=main
	uglifyjs public/js/main.js --output=public/js/main.min.js

clean:
	rm public/css/main.css

.PHONY: clean