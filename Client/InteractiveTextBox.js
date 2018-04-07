function InteractiveTextBox(textBoxID, autoCompleteContainerID, options) {
	this.optionDefaults = { 'defaultValue' : "Search For...", 'noErrorClass' : "dropDownItemContainer", 'errorClass' : "dropDownItemContainerError",
						    'dataSource' : "ARRAY", 'dataArray' : null, 'AMCREF' : null, 'AMCAPI' : null, 'searchDelay' : 350, 'AJAXURL' : "",
						    'AJAXFetchMethod' : "GET" };
	this.properties = { 'textBoxID' : textBoxID, 'autoCompleteContainerID' : autoCompleteContainerID };
	var that = this;
	//alert(alertProperties(options));
	initialiseOptions(options);
	//alert(alertProperties(this.properties));
	window.document.getElementById(that.properties['textBoxID']).value = this.properties["defaultValue"];
	addEvents();
	
	function addEvents() {
		var tb = window.document.getElementById(that.properties['textBoxID']);
		var ac = window.document.getElementById(that.properties['autoCompleteContainerID']);
		tb.onfocus = function() {
			if(this.value == that.properties['defaultValue']) {
				this.value = "";
			}
		};
		tb.onblur = function() {
			if(this.value == "") {
				this.value = that.properties['defaultValue'];
			}
		};
		var timer = null;
		tb.onkeyup = function() {
			var strVal = this.value;
			if (timer) {
				window.clearTimeout(timer);
			}
			timer = window.setTimeout( function() {
				timer = null;
				if(that.properties["dataSource"] == "AJAX") {
					that.properties["AMCAPI"].call(that.properties["AMCREF"], { 'URL' : that.properties["AJAXURL"], 'QUERY' : strVal, 'METHOD' : "GET" }, that.populateAutoComplete, that); // { 'URL' : that.properties["AJAXURL"], 'QUERY' : strVal }, this.populateAutoComplete
				}
				else if(that.properties["dataSource"] == "ARRAY") { that.getStringMatches(strVal); }
			}, that.properties["searchDelay"] );
		};
		tb = null;
	}
	
	this.getStringMatches = function(str) {
		var validItems = "";
		for(var c = 0; c < that.properties["dataArray"].length; c++) {
			if(that.properties["dataArray"][c].startsWith(str)) {
				validItems += that.properties["dataArray"][c] + "+";
			}
		}
		that.populateAutoComplete(validItems.slice(0, validItems.length - 1), "", str);
	}
	
	function alertProperties(obj) {
		var str = "";
		for(var prop in obj) {
			str += "[" + prop + "]:" + obj[prop] + "\n";
		
		}
		return str;
	}
	
	function initialiseOptions(options) {
		for(var prop in that.optionDefaults) {
			if(options.hasOwnProperty(prop)) { that.properties[prop] = options[prop]; }
			else { that.properties[prop] = that.optionDefaults[prop]; }
		}
	}
	
	this.populateAutoComplete = function(data, dataError, valueSearched) {
		var tb = window.document.getElementById(this.properties['textBoxID']);
		var ac = window.document.getElementById(this.properties['autoCompleteContainerID']);
		var itemClass = this.properties['errorClass'];
		var itemArray;
		valueSearched == "" ? itemArray = [] : itemArray = data.split("+");
		if(dataError !== true) {
			if(itemArray.length == 1 && itemArray[0] == "") {
				itemArray[0] = "No Results Matching Searched Value ' " + valueSearched + " '";
				dataError = true;
			}
			else {
				itemClass = this.properties['noErrorClass'];
			}
		}
		$("#" + ac.id).hide();
		ac.innerHTML = "";
		if(itemArray.length > 0) {
			for (var i = 0; i < itemArray.length; i++) {
				var menuItem = window.document.createElement("div");
				menuItem.setAttribute("id", ac.id + i);
				menuItem.setAttribute("class", itemClass);
				menuItem.innerHTML = itemArray[i];
				if(dataError !== true) {
					menuItem.addEventListener("click", function() {
						tb.value = this.innerHTML;
						$("#" + ac.id).hide();
						ac.innerHTML = "";
					});
				}
				ac.appendChild(menuItem);
			}
			$("#" + ac.id).show();
		}
	}
}