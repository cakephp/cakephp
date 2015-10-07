//$(document).ready(function () {
var elements = document.getElementsByClassName('cake-core-postLink');

var postLinkFunction = function (event) {
    var formName = this.getAttribute("data-cake-core-form");
    var confirmMessage = this.getAttribute('data-cake-core-confirm');
    if (confirmMessage !== null && confirmMessage !== 'undefined') {
        if (confirm(confirmMessage)) {
            document[formName].submit();
            return false;
        }
        return true;
    }
    document[formName].submit();
    event.returnValue = false;
    return false;
};

for (var i = 0; i < elements.length; i++) {
    var element = elements[i];
    element.onclick = postLinkFunction;
}
//});
