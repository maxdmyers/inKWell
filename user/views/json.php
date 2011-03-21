{
	"id"       : <%= fJSON::encode($this->pull('id')) %>,
	"classes"  : <%= fJSON::encode($this->pull('classes'))   %>,
	"contents" : <%= fJSON::encode($this->place('contents'))   %>
}
