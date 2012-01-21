{
	"id"       : <%= fJSON::encode($this->pull('id')) %>,
	"title"    : <%= JFSON::encode($this->rcombine('title')) %>,
	"classes"  : <%= fJSON::encode($this->pull('classes', array())) %>,
	"contents" : <% $this->place('contents'); %>
}
