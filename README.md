# AJAXManager
AJAXManager is currently in development.  The goal of this project is to design an all in one solution, both client side (Javascript) and server side (PHP) libraries, that give a web developer a fast and easy way to add AJAX functionality to a web project.  The goal of these libraries is to manage and hide the developer from all the intricacies of implementing AJAX by managing all the client/server communication and offer tlhe developer, APIs on both client and server side that can be used to send/receive data.

### Libraries (Brief Description)
AJAXManager repository contains 2 libraries namely AJAXManagerClient and AJAXManagerServer. These libraries can be used in conjunction along side AJAXWebComponents to provide AJAX functionality. AJAXManagerClient provides all neccessary client side AJAX functionality to each component so each component need not be concerned with the intricacities of AJAX and can have a uniformed API for fetching data. AJAXManagerServer library acts as the receiving end of AJAXManagerClient and provides the server side of AJAX functionality. This repository is being designed along side AJAXManagerClient to provide seemless AJAX transactions without the need to know all the intricacities of AJAX.

### Documentation
1. AJAXManagerClient - (Documentation not yet written)
2. AJAXManagerServer - (Documentation not yet written)

### Compatibility with other Repositories
AJAXManager is being developed along side several repositories all designed to complement and work seemlessly together.  
#### AJAXWebComponents
AJAXWebComponents is currently in development.  The goal of this project is to design a set of website components that add functionality to existing HTML DOM elements with less code by creating reusable javascript objects that handle extra functionality internally.  An example of extra functions that this project aims to provide could be auto complete on input text fields with dynamically loadable data possibly through AJAX when the text field has a value entered into it.  Individual components will be designed as single objects that can be included as and when needed in a web project.
