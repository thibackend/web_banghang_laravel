@if(count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $errors)
                <li>{!! $error !!}</li>
            @endforeach
        </ul>
    </div>
@endif