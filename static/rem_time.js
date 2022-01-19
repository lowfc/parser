timer = parseInt($('#remained').text())

if (!isNaN(timer)){
    setInterval(()=>{
        if (timer>0){
            timer--;
            $('#remained').text(timer)
        }
    }, 1000)
}