ARJformUtils = {};
ARJformUtils.trimStr=function(str){
	//console.log(typeof str);
	if( typeof str === 'string' ){
		return str.replace(/^\s+|\s+$/g, '');	
	}
	return str;
};
ARJformUtils.buildRef=function(obj,o){
//console.log('building ref!');
// CREATE REFERENCE OBEJCT
// Iterate through all fields in constructor object, and create a reference object
// PARAMETERS
// o = extjs form constructor object

//Scan through all items in items array to build a field reference list
for(var fItem in o){
//	console.log('Iterating root properties of object');
//	console.log(fItem);

	//console.log('o['+fItem+'] = '+o[fItem]);
	if(fItem === "items"){
//		console.log('- Items found, iterating sub items');
		this.buildRef(obj,o[fItem]);
//		continue;
	}
	if(o[fItem] === undefined){
		continue;
	}
	if(o[fItem].items !== undefined){
//		console.log('- Items, items found, iterating sub items');
		this.buildRef(obj,o[fItem].items);
//		continue;
	}
	if(o[fItem].xf === undefined){
		continue;
	}
//		console.log(o[fItem].name+' - '+o[fItem].xf.xft);
		//console.log(o[fItem]);
		
		switch(o[fItem].xf.xft){
			//this is a basic catch all to build references for all the field types
				//the other field types have something special about the dom structure that prevents getting a referene immediately.
			case "div":
			case "grid":
			case "txt":
			case "tx":
			case "co":
			case "date":
			case "btn":
				if(obj[o[fItem].name] !== undefined){
					//console.log(o[fItem].name);
					if(obj[o[fItem].name].xf.xft != o[fItem].xf.xft){
						continue;
					}
					obj[o[fItem].name].r[obj[o[fItem].name].r.length] = o[fItem];
				}else{
					obj[o[fItem].name] = {};
					obj[o[fItem].name].xf = o[fItem].xf;
//					obj[o[fItem].name].xf.xft = "txt";
					obj[o[fItem].name].r = [];
					obj[o[fItem].name].r[0] = o[fItem];
				}
			break;
			//checkboxes and combos are encapsulated by a div for positioning
			case "check":
			case "ch":
				if(obj[o[fItem].name] !== undefined){
					obj[o[fItem].name].r[obj[o[fItem].name].r.length] = o[fItem].items.items[0];
				}else{
					obj[o[fItem].name] = {};
					obj[o[fItem].name].xf = o[fItem].xf;
//					obj[o[fItem].name].xf.xft = "ch";
					obj[o[fItem].name].r = [];
					if(o[fItem].tag === 'div'){
						obj[o[fItem].name].r[0] = o[fItem].items.items[0];
					}else{
						obj[o[fItem].name].r[0] = o[fItem];
					}
					
				}
			/*
				obj[o[fItem].name] = {};
				obj[o[fItem].name].xft = "ch";
				//console.log('check field--');
				//console.log(o[fItem]);
				//console.log('--check field');
				obj[o[fItem].name].r = o[fItem].items.items[0];
			*/
			break;
			//radio buttons are always part of a group and you must query the group for the value
			case "radio":
			//console.log('og field ref');
			//fpaytype qPayType,
			
				if(obj[o[fItem].name] !== undefined){
					obj[o[fItem].name].r[obj[o[fItem].name].r.length] = o[fItem];
				}else{
					obj[o[fItem].name] = {};
					obj[o[fItem].name].xf = o[fItem].xf;
					obj[o[fItem].name].r = [];
					obj[o[fItem].name].r[obj[o[fItem].name].r.length] = o[fItem];
				}
			break;
			default:
				continue;
		//		obj[o[fItem].name] = {};
		//		obj[o[fItem].name].r = o[fItem];
		//		obj[o[fItem].name].DEFAULT = true;
		//		obj[o[fItem].name].xf = o[fItem].xf;
				//console.log('REF BUILD DEFAULT USED for: '+fItem)
			break;
		}
		if(o[fItem].xf.xti){
			o[fItem].tabIndex = o[fItem].xf.xti;
		}
//	}
  }

//  for(var thisfield in obj){
//  	console.log(thisfield);
//  }
  
};
ARJformUtils.clearFields=function(fieldObj){

	//console.log('Clear Fields');
	var len;
		for(var field  in fieldObj){
			//console.log(field);
			//console.log(fieldObj[field]);
				switch(fieldObj[field].xf.xft){
					//Text and Combo fields
					case 'div':
						len = fieldObj[field].r.length;
						while (len > 0){
							len--;
							//console.log(fieldObj[field].r[len]);
							fieldObj[field].r[len].body.dom.innerHTML = ' &nbsp;';
						}
					break;
					case 'co':
						len = fieldObj[field].r.length;
						while (len > 0){
							len--;
							fieldObj[field].r[len].setValue('');
						}
					break;	
					case 'txt':
						len = fieldObj[field].r.length;
						while (len > 0){
							len--;
							fieldObj[field].r[len].setValue('');
						}			
					break;
					//Check box fields
					case 'ch':
						len = fieldObj[field].r.length;
						while (len > 0){
							len--;
							fieldObj[field].r[len].setValue('');
						}
											
					break;
					//Option Group (Radio Buttons) box fields
					case 'radio':
					//	len = fieldObj[field].r.length;
						for(var ogf in fieldObj[field].r){
							//console.log(fieldObj[field].r[ogf]);
							if(fieldObj[field].r[ogf].xtype === "radio"){
								fieldObj[field].r[ogf].setValue(false);
							}
							
						}
					//	while (len > 0){
					//		len--;
					//		console.log(len);
					//		console.log(fieldObj[field].r[len]);
					//	}	
					break;
					default:
						continue;
					break;
				}
		}
};

ARJformUtils.buildFieldState=function(fieldObj ){
		//save all the values as they are populated in the form
		//this is used to save the state of all of the values after they are loaded for later use.
		//upon editing or creating a new entry, these values are loaded into the fields when the cancel button is pressed.
		
		//current form save
		var thisForm = {};
		
		var len; //reference array length
		for(var f in fieldObj){
			//console.log(f+' ='+fieldObj[f]);
			
				switch(fieldObj[f].xf.xft){
					//Text and Combo fields
					case 'co':
							len = fieldObj[f].r.length;
							while (len > 0){
								len--;
								thisForm[f] = fieldObj[f].r[len].getValue();
								
							}
					break;	
					case 'date':
					case 'txt':
						//console.log(f);
							len = fieldObj[f].r.length;
							while (len > 0){
								len--;
								thisForm[f] = fieldObj[f].r[len].getRawValue();
							}							
											
					break;
					//Check box fields
					case 'ch':
					case 'check':
						var setVal = false;
						//console.log(f+' - check field - '+f);
						//console.log(fo[f])
						//console.log(fieldObj[f])
						
						//check if field is true or 1, if so, then check the box
				/*		if(fo[f] || fo[f] > 0){
							setVal = true;
						}else{
							setVal = false;
						}
				*/	
						len = fieldObj[f].r.length;
						while (len > 0){
							len--;
							//console.log(fieldObj[f].r[len]);
							
							thisForm[f] = fieldObj[f].r[len].getValue();
						}
											
					break;
					//Option Group (Radio Buttons) box fields
					case 'radio':
						thisForm[f] = fieldObj[f].r[0].getGroupValue();
				//		console.log('og field - '+f);
				//		for(var og in fieldObj[f].r){
				//			thisForm[f] = fieldObj[f].r[len].getValue();
				//			fieldObj[f].r[fo[f]].setValue(true);
				//		}				
					break;
					default:
						//console.log(fieldObj[field]);
						//fieldObj[f].r.setValue(fo[f]);
					break;
				}
		}
		
		return thisForm;
};

ARJformUtils.countObjProperties=function(o){
	var n = 0;
	for(var c in o){
		n++;
	}
	return n;
};
//accepts 2 objects, obj and deltaObj. Returns an object with all the propertys of deltaObj, but only if they are different from that of obj.
ARJformUtils.getDeltaObject=function(obj,deltaObj){
	var returnObj = {};
	for(var property in deltaObj){
		if(obj[property] === undefined || obj[property] === deltaObj[property]) {
			continue;
		}
		returnObj[property] = deltaObj[property];
	}
	return returnObj;
};

ARJformUtils.deleteEmptyStrings=function(obj ){
	for(i in obj){
		if( ARJformUtils.trimStr(obj[i]) === '' ){
			delete obj[i];
		}
	}
	return obj;
};

ARJformUtils.populateFields=function(fieldObj,dataObj){
		//populate the form with the passed object.
		// PARAMETERS
		//fo = form object with all form parameters
		// LOGIC
		//Search through the passed object, and for each property, 
		//check if there is a corresponding field, if so, fill it with the property value
		//console.log('populate fields');
		var len = 0; //reference array length
		
		for(var f in dataObj){
			//console.log(f+' ='+dataObj[f])
			
			if(fieldObj[f] != undefined){
			
			//console.log(f+' ='+dataObj[f])
			
				switch(fieldObj[f].xf.xft){
					//Text and Combo fields
					case "co":
							len = fieldObj[f].r.length;
							while (len > 0){
								len--;
								fieldObj[f].r[len].setValue(dataObj[f]);
							}
					break;	
					case "div":
						//console.log(f);
							len = fieldObj[f].r.length;
							while (len > 0){
								len--;
								fieldObj[f].r[len].body.dom.innerHTML = dataObj[f];
							}
					break;
					case "date":
					case "txt":
						//console.log(f);
							len = fieldObj[f].r.length;
							while (len > 0){
								len--;
								fieldObj[f].r[len].setValue(dataObj[f]);
							}							
											
					break;
					//Check box fields
					case "check":
					case "ch":
						var setVal = false;
//						console.log(f+' - check field - '+f);
//						console.log(dataObj[f]);
//						console.log(dataObj[f] === true || dataObj[f] > 0);
						//console.log(fieldObj[f])
						
						//check if field is true or 1, if so, then check the box
						if(dataObj[f] === true  || dataObj[f] > 0){
							setVal = true;
						}else{
							setVal = false;
						}
						
						len = fieldObj[f].r.length;
						while (len > 0){
							len--;
							//console.log(fieldObj[f].r[len]);
							fieldObj[f].r[len].setValue(setVal);
						}
											
					break;
					//Option Group (Radio Buttons) box fields
					case "radio":
						//console.log('og field - '+f);
						//dataObjr(var og in fieldObj[f].r){
							fieldObj[f].r[dataObj[f]].setValue(true);
						//}				
					break;
					default:
						//console.log(fieldObj[field]);
						//fieldObj[f].r.setValue(dataObj[f]);
					break;
				}
			}
		}
	};

	ARJformUtils.errorMsg= function(o){
		return Ext.MessageBox.show({
			buttons: Ext.MessageBox.OK
			,icon: Ext.MessageBox.ERROR
			,modal: false
			,title: o.t
			,msg: o.e + '<BR><BR>Please refresh the page to try again.<BR><BR>If the problem persists, contact the administrator.'
		});
	};
	ARJformUtils.abortActive=function(id ){
		//if(Ext.Ajax.isLoading(id) ){
			Ext.Ajax.abort(id);
		//}
	};
	ARJformUtils.sendData=function(url, p, c, Csc ){
		//p,c,Csc
		//p = extraParams to send in XHR
		//c = callback func
		//Csc = callback func scope
			//Csc is called with the following parameters:
			//	1 - BOOL - success or false of JSON
			//	2 - OBJ - JSON Decoded response
			
		//var conn = new Ext.data.Connection({});
		return Ext.Ajax.request({
			params:p,
			method:'POST',
			url:url,
			callback: function(o,s,r){
				// o = The parameter to the request call.
				// s = True if the request succeeded.
				// r = The XMLHttpRequest object containing the response data
				//		http://www.w3.org/TR/XMLHttpRequest/
				
				//debug
				//console.log(s);
				//console.log(r);
				
				if(s){
				//connection successful, but is data good?
				
					//make sure there is content at all
					if(r.getResponseHeader['Content-Length'] < 1){
						ARJformUtils.errorMsg({
							t:'FATAL ERROR!',
							e:"ERROR 201<BR><BR>Content-Length: "+r.getResponseHeader['Content-Length']
						});
						//c(false);
						c.call(Csc,false);
						return false;
					}
					
					try {
						//If the JSON is invalid, this function throws a SyntaxError. 
						var resp = Ext.util.JSON.decode(r.responseText);
					}
					catch(e) {
						//if data = "hjkjkj is not defined"
						// error name: "ReferenceError"
						ARJformUtils.errorMsg({
							t:'FATAL ERROR!',
							e:"ERROR 202<BR><BR>Invalid JSON Data Returned!"
						});
						//c(false);
						c.call(Csc,false);
						return false;
					}
					//debug
				
					//at this point, the data could be good, but the JSON data could be bad
					// check if success variable is defined at all
					//if success is defined, then there is a high chance the rest of the data is good

					//console.log(resp);
					
					if(!resp.success){
						var errorMsg;
						if(resp.errors && resp.errors.desc){
							errorMsg = resp.errors.desc
						}else{
							errorMsg = 'Error processing request.'
						}
						ARJformUtils.errorMsg({
							t: 'Error!'
							,e: errorMsg
						});
						//c(false);
						c.call(Csc,false);
						return false;
					}
					
					
					c.call(Csc,true,resp);
					//c(true,resp.d);
				
				}else{
					// enter this branch with s == false
					// if success = false, output error 100, HTTP status text
	
					ARJformUtils.errorMsg({
						t:'FATAL CONNECTION ERROR!',
						e:"ERROR 100<BR><BR>Data Connection Error<BR><BR>Status: "+r.status+", "+r.statusText
					});
					//c(false);
					c.call(Csc,false);
					return false;
				}
			}
		});
	};
	