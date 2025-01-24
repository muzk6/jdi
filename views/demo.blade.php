<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>

<h1>{{ $title }}</h1>
<form method="post" action="/demo/doc">
    {!! csrf_field() !!}
    <input type="text" name="first_name" value="{{ $first_name }}">
    <input type="text" name="last_name" value="{{ $last_name }}">
    <button>Doc Submit</button>
    <input type="button" onclick="xhr()" value="XHR Submit"/>
</form>
<label style="display: block; margin-top: 30px;">{{ $user_id ? "ID: {$user_id} 已登录" : '未登录' }}</label>
<form style="display: inline-block" method="post" action="/demo/login">
    {!! csrf_field() !!}
    <label>User ID: <input name="user_id"></label>
    <button>Login</button>
</form>
<form style="display: inline-block" method="post" action="/demo/logout">
    {!! csrf_field() !!}
    <button>Logout</button>
</form>
<script>
@if (flash_has('msg'))
alert('{{ flash_get('msg') }}');
@endif

@if (flash_has('data'))
alert('{!! json_encode(flash_get('data')) !!}');
@endif

function xhr() {
    let data = {};
    document.querySelectorAll('form input[name]').forEach(function (input) {
        data[input.getAttribute('name')] = input.value;
    });

    fetch('/demo/xhr', {
        method: 'post',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        body: new URLSearchParams(data).toString(),
    }).then(resp => {
        if (!resp.ok) {
            return
        }

        resp.json().then(data => {
            alert(JSON.stringify(data));
        });
    });
}
</script>
</body>
</html>