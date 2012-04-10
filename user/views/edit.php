<h3>Create or Edit the Page @ <%= fHTML::encode(fURL::get()) %></h3>

<form action="" method="post">
	<textarea rows="20" cols="100" name="source"><%= fHTML::encode($this->pull('source')) %></textarea>
	<button type="submit" name="action" value="save">Save</button>
	<button type="submit" name="action" value="preview">Preview</button>
</form>
