{
	"id"       : <%= fJSON::encode($this->pull('id'))         %>,
	"classes"  : <%= fJSON::encode($this->pull('classes'))    %>,
	"contents" : <% $this->place('contents'); %>
}
