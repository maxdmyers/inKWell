{
	"id"       : <%= fJSON::encode($this->pull('id')) %>,
	"class"    : <%= fJSON::encode($this->peel('classes'))   %>,
	"contents" : <%= fJSON::encode($this->pull('contents'))   %>
}
