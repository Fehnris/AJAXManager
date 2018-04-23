## AJAXManager return data object format.
JSON = {
'ERROR' : [{ 'LAYER' : '', 'MESSAGE' : '' }],
'ERRORS' : true/false,
'DATA' : { 'TYPE' : '', 'DATA' : '' },
'TOKEN' : ''
}
	   
#### 'ERROR' : [{ 'LAYER' : '', 'MESSAGE' : '' }]
Array of error messages.  Layer = part of process error happened.  Message = String error.
#### 'ERRORS' : true/false
If there are errors this is true or false for no errors.
#### 'DATA' : { 'TYPE' : '', 'DATA' : '' }
The AJAX information being sent.  Type = String or Object or Null.  Data = The raw data.
#### 'TOKEN' : ''
The authentication key to provide security to resources.  Stored as a string.
