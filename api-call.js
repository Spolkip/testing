function callBack(element) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            this.responseText;
       }
    };
    xhttp.open("GET", "index.php/?action=" + element.getAttribute("name"), true);
    xhttp.send();
}

window.addEventListener("load", () => {
    let authButtons = document.querySelectorAll("[name *= auth-button-]"); //https://www.w3schools.com/cssref/css_selectors.php
    authButtons.forEach(function(button){
        button.addEventListener("click", function(){
            console.log(this)
            //callBack(this); //κάτσε διάβασε τι κάνει το This
        }); //σε addEventListener δεν μπορώ να βάλω παραμέτρους άρα αυτό που κάνω είναι ανοίγω ένα function
    });
})