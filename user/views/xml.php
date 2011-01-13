<?xml version="1.0" encoding="UTF-8" ?>
<response id="<%= fXML::encode($this->pull('id')) %>" class="<%= fXML::encode($this->peel('classes')) %>">
	<%= fXML::encode($this->pull('contents')) %>
</response>
