<?php include_once('links.php') ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parser</title>
</head>

<body>
    <div class="context container-fluid d-flex justify-content-center align-items-center p-0">
        <div class="row w-50" style="border-radius: 20px;">
            <div id='central_div' class="col rounded p-4">
                <h3 class="text-center mb-4">Парсинг сайта etp.eltox</h3>
                <div class="col-xs-1" style="margin: 0 auto; width: 150px;">
                    <button id='go_button' class="btn btn-primary btn-xs w-100" type="button">Спарсить!</button>
                </div>
                <br>
                <div style="width: 300px; margin: 0 auto;">
                    <span>Начиная со страницы № </span>
                    <input id='from_page_id' style='width: 90px;' type="text" value="1"><br><br>
                </div>
                <div style="width: 300px; margin: 0 auto;">
                    <span>Парсить страниц: </span>
                    <input id='page_count' style='width: 90px;' type="text" value="1">
                </div>
                <br>
                <div style="width: 300px; margin: 0 auto;">
                    <span>Время обработки: </span><span id='processing_time'>10</span><span> секунд.</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Animated squares -->
    <div class="area">
        <ul class="circles">
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>
</body>

<script>
    function recount_time(){
        pc =  parseInt($('#page_count').val())
        valid_code = 0
        // 0 - not valid; 1 - valid, bad; 2 - valid, ok
        if (!isNaN(pc)){
            time = pc * 10
            if (time <= 100){
                valid_code = 2
            }
            else if (time <= 200){
                valid_code = 1
            }
            else {
                valid_code = 0
            }
        }
        if (valid_code == 0){
            $('#processing_time').css('color', 'red')
            $('#processing_time').text('∞')
            $('#go_button').prop('disabled', true)
        }
        else if (valid_code == 1){
            $('#processing_time').css('color', 'yellow')
            $('#processing_time').text(time)
            $('#go_button').prop('disabled', false)
        }
        else{
            $('#processing_time').css('color', 'green')
            $('#processing_time').text(time)
            $('#go_button').prop('disabled', false)
        }
            
    }

    function validate_and_show(data) {
        if (data['error'] == 1) {
            $('#central_div').html(`
            <div class="d-flex justify-content-center">
                <img src="https://img.icons8.com/nolan/64/cancel-2.png"/>
                <span style='margin-left: 16px; margin-top: 15px;'>Произошла внутренняя ошибка при парсе документа, отчет сохранен.
                <a href='#' onclick='go_parse()'>Попробовать еще раз?</a></span>
            </div>
        `)
        return
        }
        let table = ''
        meta = data['data']
        for (i = 0; i < meta.length; i++){
            table += '<tr>'
            table += `<th scope="row">${i+1}</th>`
            table += `<td>${meta[i]['proc_num']}</td>`
            table += `<td>${meta[i]['ooc_proc_num']}</td>`
            table += `<td><a href='${meta[i]['link']}'>${meta[i]['link']}</a></td>`
            table += `<td>${meta[i]['mails']}</td>`
            table += `<td>${meta[i]['file_name']}</td>`
            table += `<td><a href='${meta[i]['file_link']}'>${meta[i]['file_link']}</a></td>`
            console.log(meta[i])
            table += '</tr>'
        }
        $('.context').html(`
        <table class="table">
        <thead>
            <tr>
            <th scope="col">#</th>
            <th scope="col">Номер процедуры</th>
            <th scope="col">ООС номер процедуры</th>
            <th scope="col">Ссылка</th>
            <th scope="col">Почта</th>
            <th scope="col">Название файла</th>
            <th scope="col">Ссылка на файл</th>
            </tr>
        </thead>
        <tbody>
        ` + table + '</tbody></table>')
        $('.area').remove()
        $('.context').css('top', '0px')
        $('.context').css('background-color', 'white')
    }
</script>

<script>
    // Sending request
    function go_parse() {

        page_count = 1
        from_page = 1
        if (!isNaN(parseInt($('#page_count').val()))){
            page_count = parseInt($('#page_count').val())
        }
        if (!isNaN(parseInt($('#from_page_id').val()))){
            from_page = parseInt($('#from_page_id').val())
        }

        $('#central_div').html(`
        <style>
        span{
            margin-left: 16px;
            margin-top:3px;
        }
        </style>
        <div class="d-flex justify-content-center">
            <div class="spinner-border text-warning" role="status">
            </div>
            <span>Работаем... Осталось примерно </span><span id="remained">${page_count*10}</span><span> секунд</span>
        </div>
        `)

        $.getScript('/static/rem_time.js')

        answ = $.ajax({
            method: "GET",
            url: `/back/parse.php?from_page=${from_page}&page_count=${page_count}`,
            success: (data) => {
                validate_and_show(data)
            },
            error: (jqXHR, except) => {
                alert()
                $('#central_div').html(`
                <div class="d-flex justify-content-center">
                    <img src="https://img.icons8.com/nolan/64/--tinder.png"/>
                    <span style='margin-left: 10px; margin-top:25px;'>Сервер оффлайн или недоступен =(</span>
                </div>
                <br><br>
                <div class="d-flex justify-content-center">
                    <small class="text-muted">Status: ${jqXHR.status}, exception: "${except}", ready status: ${jqXHR.readyState}.</small>
                </div>
                `)
            }
        }
        )
    }
    $('#go_button').on("click", go_parse)
    $('#page_count').on('keyup', recount_time)
</script>

</html>
