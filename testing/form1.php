<!doctype HTML>
<html>
<head>
</head>
<body>

<form action="http://localhost:8888/core/process.php" method="post">
    <input type="hidden" name="form_tools_initialize_form" value="1" />
    <input type="hidden" name="form_tools_form_id" value="8" />
    <div>
        <input type="text" name="textfield" value="Field 1" />
    </div>
    <div>
        <textarea name="textarea">Values!</textarea>
    </div>
    <div>
        <select name="dropdown">
            <option value="first">First</option>
            <option value="second">Second</option>
            <option value="third">Third</option>
        </select>
    </div>
    <div>
        <input type="radio" name="radios" value="first" checked="checked">First
        <input type="radio" name="radios" value="second">Second
        <input type="radio" name="radios" value="third">Third
    </div>
    <div>
        <input type="checkbox" name="checkboxes[]" value="first" checked="checked">First
        <input type="checkbox" name="checkboxes[]" value="second">Second
        <input type="checkbox" name="checkboxes[]" value="third">Third
    </div>
    <p>
        <input type="submit" value="Submit" />
    </p>
</form>

</body>
</html>
