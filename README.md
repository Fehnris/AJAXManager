# AJAXManager
AJAXManager is currently in development.  The goal of this project is to design an all in one solution, both client side (Javascript) and server side (PHP) libraries, that give a web developer a fast and easy way to add AJAX functionality to a web project.  The goal of these libraries is to manage and hide the developer from all the intricacies of implementing AJAX by managing all the client/server communication and offer the developer, APIs on both client and server side that can be used to send/receive data.

### Libraries (Brief Description)
AJAXManager repository contains 2 libraries namely AJAXManagerClient and AJAXManagerServer. AJAXManagerClient provides all neccessary client side AJAX functionality (written in JAVASCRIPT). AJAXManagerServer library acts as the receiving end of AJAXManagerClient and provides the server side of AJAX functionality.

### Features
#### 1. Built in communication security.
AJAXManager implements communication security by the use of key identification.  AJAX transactions between client and server use a random key for every transaction.  This random key is unique based on several variables including visitors IP Address/Client details, SessionID and more.  While some of these details are not always available it does offer a way to determine the consistency of a visitor by checking for a change or absence of information during each transaction.  Because each key is based on visitor information the key used should match the visitor information.  If there is a missmatch it can be determined that the request is non-legitimate.  These random keys are a one time use.  With every new successful AJAX transaction between client and server, a new key is supplied for use on the next transaction.  While not 100% secure this feature does provide server side resources a good amount of protection from non-web application requests.

### Documentation
1. AJAXManagerClient - (Documentation not yet written)
2. AJAXManagerServer - (Documentation not yet written)

### Compatibility with other Repositories
AJAXManager is being developed along side several repositories all designed to complement and work seemlessly together.  
#### AJAXWebComponents
AJAXWebComponents is currently in development.  The goal of this project is to design a set of website components that add functionality to existing HTML DOM elements with less code by creating reusable javascript objects that handle extra functionality internally.  An example of extra functions that this project aims to provide could be auto complete on input text fields with dynamically loadable data possibly through AJAX when the text field has a value entered into it.  Individual components will be designed as single objects that can be included as and when needed in a web project.
