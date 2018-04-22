### AJAXManager return data object format.


var JSON = {
		'ERROR' : [{ 'LAYER' : '', 'MESSAGE' }],	
		'LASTERROR' : '',				
		'DATA' : { 'TYPE' : '', 'DATA' : '' },		
		'TOKEN' : ''					
	   }
	   
	   //Array of error messages.  Layer = part of the process error happened.  Message = String error.
	   //The last error that occurred from ERROR.  String error message.
	   //The AJAX information being sent.  Type = String or Object or Null.  Data = The raw data.
	   //The authentication key to provide security to resources.  Stored as a string.
