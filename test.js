$(init_buttons);

var buttonList = [];

function Button() {
	this.id = buttonList.length;
	this.dom = $('<button>').html(buttonList.length + 1).css({
		'display':'block',
		'font-size': '20px',
		'margin':'0 0 4px 0',
		'width':'150px',
		'cursor':'pointer'
	});
	this.next = null;
	this.dom.bind('click', function(self){
		return function(event){
			self.moveCicle(event);
		}
	}(this));
	$('body').append(this.dom);

	if (buttonList.length)
	{
		this.next = buttonList[0];
		var last = buttonList[buttonList.length-1];
		last.next = this;
	}
	buttonList.push(this);
}

Button.prototype.hlnext = function(event) {
	this.next.dom.html(this.next.dom.html()+'+');
};

Button.prototype.moveCicle = function(event) {
	var value = this.dom.html();
	var curr = this;
	for (var i = 0; i < buttonList.length - 1; i++) {
		curr.dom.html(curr.next.dom.html());
		curr = curr.next;
	}
	curr.dom.html(value);
};

function init_buttons() {
	$('<button>').html('add').click(function(){new Button()}).appendTo($('body'));

	for (var i = 1; i <= 3; i++)
		new Button();
}
