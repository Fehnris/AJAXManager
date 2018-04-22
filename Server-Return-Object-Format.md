### AJAXManager return data object format.


var JSON = {
		'ERROR' : [{ 'LAYER' : '', 'MESSAGE' }],	//Array of error messages.  Layer = part of the process error happened.  Message = String error.
		'LASTERROR' : '',				//The last error that occurred from ERROR.  String error message.
		'DATA' : { 'TYPE' : '', 'DATA' : '' },		//The AJAX information being sent.  Type = String or Object or Null.  Data = The raw data.
		'TOKEN' : ''					//The authentication key to provide security to resources.  Stored as a string.
	   }