function AJAXManagerClient() {
	this.k = "";
	this.kLock = false;
  	this.queue  = [];
  	this.offset = 0;
	this.AJAXObjectRef = {};
	
	this.getLength = function(){
    	return (this.queue.length - this.offset);
	}

	this.isEmpty = function(){
    	return (this.queue.length == 0);
	}

	this.enqueue = function(item){
    	this.queue.push(item);
	}

	this.dequeue = function(){
    	if (this.queue.length == 0) return undefined;
		var item = this.queue[this.offset];
    	if (++ this.offset * 2 >= this.queue.length){
			this.queue  = this.queue.slice(this.offset);
			this.offset = 0;
    	}
    	return item;
	}

  	this.peek = function(){
    	return (this.queue.length > 0 ? this.queue[this.offset] : undefined);
  	}
	
	this.fillAutoComplete = function(str, tID, textBoxID, self, e) {
		var destID = self.AJAXObjectRef[[textBoxID]][[e]]["destinationID"];
		if (str.length == 0) { 
			if(self.peek() == tID) { var item = self.dequeue(); }
			self.kLock = false;
			window.document.getElementById(destID).innerHTML = "";
			self.hideAutoComplete("#" + destID);
			return;
		}
		else if(self.isAlphaNumeric(str)) {
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.addEventListener("error", function() {
				self.formatDropDownItems(["Server Connection Error"], "dropDownItemContainerError", textBoxID, self, e);
				self.showAutoComplete("#" + destID);
				if(self.peek() == tID) { var item = self.dequeue(); }
				self.kLock = false;
			});
			xmlhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					var AJAX = JSON.parse(this.responseText);
					if(AJAX.TOKEN == "") {
						self.formatDropDownItems([AJAX.query], "dropDownItemContainerError", textBoxID, self, e);
						self.showAutoComplete("#" + destID);
						if(self.peek() == tID) { var item = self.dequeue(); }
						self.kLock = false;
					}
					else {
						var dealerNames = new Array();
						var dealerNameList = "";
						var itemClass = "";
						if(AJAX.query == "") {
							dealerNames[0] = "No Results Beginning With ' " + str + " '";
							itemClass = "dropDownItemContainerError";
						}
						else {
							dealerNames = AJAX.query.split("+");
							itemClass = "dropDownItemContainer";
						}
						self.formatDropDownItems(dealerNames, itemClass, textBoxID, self, e);
						self.showAutoComplete("#" + destID);
						self.k = AJAX.TOKEN;
						if(self.peek() == tID) { var item = self.dequeue(); }
						self.kLock = false;
					}
				}
			};
			xmlhttp.open("GET", encodeURI(self.AJAXObjectRef[[textBoxID]][[e]]["URL"] + "?q=" + str + "&k=" + self.k), true);
			xmlhttp.send();
		}
		else {
			self.formatDropDownItems("No Dealer Names Beginning With ' " + str + " '", "dropDownItemContainerError", textBoxID, self, e);
			self.showAutoComplete("#" +self.AJAXObjectRef[[textBoxID]]["destinationElementID"]);
			if(self.peek() == tID) { var item = self.dequeue(); }
			self.kLock = false;
			return;
		}
	}
	
	this.fetchClickData = function(str, tID, textBoxID, self, e) {
		if (str.length == 0) { 
			query = "";
		}
		else {
			query = "&q=" + str;
		}
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.addEventListener("error", function() {
			if(self.peek() == tID) { var item = self.dequeue(); }
			self.kLock = false;
		});
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				
				var AJAX = JSON.parse(this.responseText);
				if(self.AJAXObjectRef[[textBoxID]][[e]]["postFormatting"] == undefined) {
					self.AJAXObjectRef[[textBoxID]][[e]]["destinationID"] = AJAX.query;
				}
				else {
					//self.AJAXObjectRef[[textBoxID]][[e]]["destinationID"] = call(self.AJAXObjectRef[[textBoxID]][[e]]["postFormatting"], AJAX);
				}
				self.k = AJAX.TOKEN;
				if(self.peek() == tID) { var item = self.dequeue(); }
				self.kLock = false;
			}
		};
		xmlhttp.open("GET", encodeURI(self.AJAXObjectRef[[textBoxID]][[e]]["URL"] + "?k=" + self.k + query), true);
		xmlhttp.send();
	}

	this.kLockCheck = function(tID, callback, str, counter, textBoxID, self, e) {
		if(self.kLock == false && tID == self.peek()) {
			self.kLock = true;
			callback(str, tID, textBoxID, self, e);							   
		}
		else{
			setTimeout(function() { self.kLockCheck(tID, callback, str, counter++, textBoxID, self, e) }, 100);
		}
	}

	this.isAlphaNumeric = function(str) {
		var code, i, len;
		for (i = 0, len = str.length; i < len; i++) {
			code = str.charCodeAt(i);
			if (!(code > 31 && code < 33) && 
				!(code > 38 && code < 42) && 
				!(code > 44 && code < 47) && 
				!(code > 47 && code < 58) && 
				!(code > 64 && code < 91) && 
				!(code > 96 && code < 123)) { 
				return false;
			}
		}
		return true;
	}

	this.formatDropDownItems = function(itemArray, itemClass, destID, self, e) {
		var match = false;
		if(self == this) { match = true; }
		alert("this:" + this + " self: " + self + " match: " + match);
		var returnStr = "";
		var textField = window.document.getElementById(destID);
		var container = window.document.getElementById(self.AJAXObjectRef[[destID]][[e]]["destinationID"]);
		container.innerHTML = "";
		for (var i = 0; i < itemArray.length; i++) {
			var menuItem = window.document.createElement("div");
			menuItem.setAttribute("id", container.id + i);
			menuItem.setAttribute("class", itemClass);
			menuItem.innerHTML = itemArray[i];
			if(itemClass !== "dropDownItemContainerError") {
			menuItem.addEventListener("click", function() {
				textField.value = this.innerHTML;
				self.hideAutoComplete("#" + container.id);
				container.innerHTML = "";
			});
			}
			container.appendChild(menuItem);
			//returnStr += "<div class='" + itemClass + "' id='" + containerID + i + "'>" + itemArray[i] + "</div>";
		}
		//return returnStr;
	}

	this.Authorise = function(AJAXAuthURL, authObj) {
		document.cookie = authObj["CN"] + "=" + authObj["CV"] + ";path=/";
		this.sendAuth(AJAXAuthURL, authObj["CN"]);
	}

	this.showAutoComplete = function(textBox) {
		$(textBox).show();
	}

	this.hideAutoComplete = function(textBox) {
		$(textBox).hide();
	}

	this.sendAuth = function(url, CookieName) {
		var self = this;
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.addEventListener("error", function() {});
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				var AJAX = self.validateResponse(this.responseText);
				self.k = AJAX.TOKEN;
			}
		};
		xmlhttp.open("GET", encodeURI(url + "?t=" + CookieName), true);
		xmlhttp.send();
	}
	
	this.addAJAXEventListener = function(eventType, elementID, eventOptions) {
		this.AJAXObjectRef[[elementID]] = { [[eventType]] : eventOptions };
		if(eventType == "onkeyup") {
			if(this.AJAXObjectRef[[elementID]][[eventType]]["onkeyup.delay"] == undefined) {
				this.AJAXObjectRef[[elementID]][[eventType]]["onkeyup.delay"] = 400;
			}
			if(this.AJAXObjectRef[[elementID]][[eventType]]["elementType"] == "input.text") {
				this.onKeyUpAutoCompleteHandler(elementID, this.fillAutoComplete, eventType);
			}
		}
		else if(eventType == "click") {
			if(this.AJAXObjectRef[[elementID]][[eventType]]["click.delay"] == undefined) {
				this.AJAXObjectRef[[elementID]][[eventType]]["click.delay"] = 400;
			}
			if(this.AJAXObjectRef[[elementID]][[eventType]]["clickLock"] == undefined) {
				this.AJAXObjectRef[[elementID]][[eventType]]["clickLock"] = false;
			}
			if(this.AJAXObjectRef[[elementID]][[eventType]]["AJAXQuery"] == undefined) {
				this.AJAXObjectRef[[elementID]][[eventType]]["AJAXQuery"] = "";
			}
			this.clickHandler(elementID, this.fetchClickData, eventType);
		}
	}
	
	this.validateResponse = function(json) {
		var response = this.constructReturnObject();
		if(json !== null) {
			try {
				a = JSON.parse(json);
				if(a && typeof a === 'object' && a.constructor === Object) {
					var isValidReturn = this.validateReturnObject(a);
					//alert("isValidReturn:" + isValidReturn);
					if(isValidReturn) {
						if(a.ERRORS == true) {
							response.ERROR = this.setErrors(response.ERROR, a.ERROR);
						}
						response.DATA.TYPE = a.DATA.TYPE;
						response.DATA.VALUE = a.DATA.VALUE;
						response.TOKEN = a.TOKEN;
					}
					else {
						response.ERROR = this.setError(response.ERROR, "INVALID", 100);
						response.ERRORS = true;
					}
				}
				else {
					response.ERROR = this.setError(response.ERROR, "NOT OBJECT", 100);
					response.ERRORS = true;
				}
			} catch (e) {
				response.ERROR = this.setError(response.ERROR, "NOT JSON", 100);
				response.ERRORS = true;
			}
		}
		return response;
	}
	
	this.validateReturnObject = function(returnObj) {
		if(returnObj.ERROR == undefined) {
			//alert("ERROR");
			return false;
		}
		if(returnObj.ERRORS == undefined) {
			//alert("ERRORS");
			return false;
		}
		if(returnObj.DATA.TYPE == undefined || returnObj.DATA.VALUE == undefined) {
			//alert("DATA");
			return false;
		}
		if(returnObj.TOKEN == undefined) {
			//alert("TOKEN");
			return false;
		}
		return true;
	}
	
	this.setErrors = function(errorObj, error) {
		var errorLength = error.length;
		for(i = 0; i < errorLength; i++) {
			errorObj.push(error[i]);
		}
		return errorObj;
	}
	
	this.setError = function(errorObj, error, layer) {
		errorObj.push({ 'LAYER' : layer, 'MESSAGE' : error });
		return errorObj;
	}
	
	this.constructReturnObject = function() {
		var returnObj = { 'ERROR' : [],
						  'ERRORS' : false,
						  'DATA' : { 'TYPE' : null, 'VALUE' : "NULL" },
						  'TOKEN' : ""
						}
		return returnObj;
	}
	
	this.clickHandler = function(htmlObjectID, callback, e) {
		htmlObject = window.document.getElementById(htmlObjectID);
		var self = this;
		var timer = null;
		htmlObject.onclick = function() {
			if(self.AJAXObjectRef[[htmlObjectID]][[e]]["clickLock"] == false) {
				self.AJAXObjectRef[[htmlObjectID]][[e]]["clickLock"] = true;
				var strVal = self.AJAXObjectRef[[htmlObjectID]][[e]]["AJAXQuery"];
				var tID = Math.floor(Math.random() * 65535);
				self.enqueue(tID);
				self.kLockCheck(tID, callback, strVal, 0, htmlObjectID, self, e);
				timer = window.setTimeout( function() {
					timer = null;
					self.AJAXObjectRef[[htmlObjectID]][[e]]["clickLock"] = false;
				}, self.AJAXObjectRef[[htmlObjectID]][[e]][e + ".delay"] );
			}
		};
		htmlObject = null;
	}
	
	this.onKeyUpAutoCompleteHandler = function(textBoxID, callback, e) {
		textBox = window.document.getElementById(textBoxID);
		var self = this;
		var timer = null;
		textBox.onkeyup = function() {
			var strVal = this.value;
			if (timer) {
				window.clearTimeout(timer);
			}
			timer = window.setTimeout( function() {
				timer = null;
				var tID = Math.floor(Math.random() * 65535);
				self.enqueue(tID);
				self.kLockCheck(tID, callback, strVal, 0, textBoxID, self, e);
			}, self.AJAXObjectRef[[textBoxID]][[e]][e + ".delay"] );
		};
		textBox = null;
	}
	
	this.addToQueue = function(searchParams, callback, callbackObj) {
		var self = this;
		var tID = Math.floor(Math.random() * 65535);
		self.enqueue(tID);
		self.kLockCheckAPI(tID, { 'function' : callback, 'object' : callbackObj }, searchParams, 0, self);
	}
	
	this.kLockCheckAPI = function(tID, callback, searchParams, counter, self) {
		if(self.kLock == false && tID == self.peek()) {
			self.kLock = true;
			self.fetchDataAPI(tID, callback, searchParams, self);							   
		}
		else { setTimeout(function() { self.kLockCheckAPI(tID, callback, searchParams, (counter + 1), self) }, 100); }
	}
	
	this.fetchDataAPI = function(tID, callback, searchParams, self) {
		var str = "";
		if (searchParams["QUERY"].length > 0) {
			//if(self.isAlphaNumeric(searchParams["QUERY"])) {
				str = "&q=" + encodeURIComponent(searchParams["QUERY"]);
			//}
			/*else {
				callback["function"].call(callback["object"], "Invalid Search Criteria", true, searchParams["QUERY"]); //return { 'data' : "", 'error' : "Could not contact Server" }
				if(self.peek() == tID) { var item = self.dequeue(); }
				self.kLock = false;
				return;
			}*/
		}
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.addEventListener("error", function() {
			var AJAX = self.constructReturnObject();
			AJAX.ERROR[0].LAYER = 100;
			AJAX.ERROR[0].MESSAGE = "Count not contact Server";
			delete AJAX.TOKEN;
			callback["function"].call(callback["object"], AJAX, searchParams["QUERY"]); //return { 'data' : "", 'error' : "Could not contact Server" }
			self.k = "";
			if(self.peek() == tID) { var item = self.dequeue(); }
			self.kLock = false;
		});
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4) {
				if(this.status == 200) {
					var AJAX = self.validateResponse(this.responseText);
					var TOKEN = AJAX.TOKEN;
					delete AJAX.TOKEN;
					if(self.peek() == tID) { var item = self.dequeue(); }
					self.kLock = false;
					if(AJAX.TOKEN !== "") {
						self.k = TOKEN;
					}
					else {
						self.k = "";
					}
					callback["function"].call(callback["object"], AJAX, searchParams["QUERY"]); //return { 'data' : "", 'error' : AJAX.query }
				}
				else if(this.status = 404) {
					var AJAX = self.constructReturnObject();
					AJAX.ERROR[0].LAYER = 100;
					AJAX.ERROR[0].MESSAGE = "Could not find Page";
					delete AJAX.TOKEN;
					callback["function"].call(callback["object"], AJAX, searchParams["QUERY"]); //return { 'data' : AJAX.query, 'error' : "" }
					self.k = "";
					if(self.peek() == tID) { var item = self.dequeue(); }
					self.kLock = false;
				}
			}
		};
		if(searchParams["METHOD"] == "GET") {
			xmlhttp.open("GET", searchParams["URL"] + "?k=" + encodeURIComponent(self.k) + str, true);
			xmlhttp.send();
		}
		else if(searchParams["METHOD"] == "POST") {
			xmlhttp.open("POST", searchParams["URL"], true);
			xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			xmlhttp.send("k=" + encodeURIComponent(self.k) + str);
		}
	}
}