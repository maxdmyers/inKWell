<h3>Create or Edit the Page @ <%= fHTML::encode(fURL::get()) %></h3>

<form action="" method="post">
	<textarea rows="20" cols="100"><%= fHTML::encode($this->pull('source')) %></textarea>
	<button>Save</button>
</form>
