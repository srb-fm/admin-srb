// Focus auf erstes Element der toolbar setzen
window.onload = function() {
   var element = document.getElementById("first_element_of_toolbar");
   element.scrollIntoView();
   element.tabIndex = -1;
   element.focus();
}
