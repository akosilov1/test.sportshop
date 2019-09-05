<script>
    if( next = document.getElementById("END")){
        var st = next.attributes["data-st"].value * 1;
        var c = next.attributes["data-count"].value * 1;
        console.log(c-st);
        if(st < c){
            console.log("if");
            setTimeout(function(){
                    console.log("submit");
                    document.forms[0].submit();
                },
                1000);
        }
    }

    if(a = document.getElementById("unext")) setTimeout(function(){a.click()},600);
</script>
</body>
</html>