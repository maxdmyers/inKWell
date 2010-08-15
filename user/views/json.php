{
	"status" : <?= fJSON::encode($this->pull('status')) ?>,
	"type"   : <?= fJSON::encode($this->pull('type'))   ?>,
	"data"   : <?= fJSON::encode($this->pull('data'))   ?>
}
