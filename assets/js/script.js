function changePage(skip) {
    console.log("Page change");
    console.log(skip);
    document.getElementById("skip").value = skip;
    document.forms["projectForm"].submit(); // Submit the form
    
}

