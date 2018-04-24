## AJAXManager return data object format.
JSON = {
'ERROR' : [{ 'LAYER' : '', 'MESSAGE' : '' }],
'ERRORS' : true/false,
'DATA' : { 'TYPE' : '', 'VALUE' : '' },
'TOKEN' : ''
}
	   
#### 'ERROR' : [{ 'LAYER' : '', 'MESSAGE' : '' }]
Array of error messages.  Layer = part of process error happened.  Message = String error.
#### 'ERRORS' : true/false
If there are errors this is true or false for no errors.
#### 'DATA' : { 'TYPE' : '', 'VALUE' : '' }
The AJAX information being sent.  Type = The type of data being sent.  String or Object or Null.
VALUE = The data itself.
#### 'TOKEN' : ''
The authentication key to provide security to resources.  Stored as a string.
