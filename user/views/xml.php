<?xml version="1.0" encoding="UTF-8" ?>
<response status="<%= fXML::encode($this->pull('status')) %>" type="<%= fXML::encode($this->pull('type')) %>">
	<%= fXML::encode($this->pull('data')) %>
</response>
