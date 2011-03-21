<?xml version="1.0" encoding="UTF-8" ?>
<response id="<%= fXML::encode($this->pull('id')) %>" classes="<%= fXML::encode($this->combine('classes', ' ')) %>">
	<%= fXML::encode($this->place('contents')) %>
</response>
