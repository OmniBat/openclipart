SRC = $(shell find styles -name "*.less" -type f | sort)


css: $(SRC) 
	lessc styles/main.less > public/css/main.css

clean:
	rm public/css/main.css

.PHONY: clean