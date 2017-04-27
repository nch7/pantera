@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Personal Assistant
                </div> 
                <div class="panel-body">
                    <div id="chat">
                        <div id="chat-messages">
                        </div>
                        <div class="form-group clearfix">
                            <div class="col-xs-11 nopadding-left">
                                <input type="text" name="message" class="form-control">
                            </div>
                            <div class="col-xs-1 nopadding-right">
                                <button id="send-btn" class="btn btn-primary">Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script type="text/javascript">
        
        function scrollDown() {
            $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
        }

        function loadNewMessages() {
            $.ajax({
                method: "GET",
                url: "/message",
                success: function(message) {
                    if(message) {
                        $("#chat-messages").append('<div class="clearfix"><div class="message col-xs-8 nopadding-left pull-left received"><div class="panel panel-primary"><div class="panel-heading"> <h3 class="panel-title">PA Bot</h3> </div> <div class="panel-body">'+message+'</div> </div></div> </div>');    
                        
                        scrollDown();              
                    }   
                    setTimeout(loadNewMessages, 100);   
                }
            })
        }

        $("#send-btn").click(function() {
            var message = $("input[name='message']").val();
            var name = "{{ Auth::user()->name }}";
            $("input[name='message']").val("");
            $.ajax({
                url: "/message",
                method: "POST",
                data: {
                    message: message
                },
                success: function() {
                    $("#chat-messages").append('<div class="clearfix"><div class="message col-xs-8 nopadding-left pull-right sent"><div class="panel panel-info"><div class="panel-heading"> <h3 class="panel-title">'+name+'</h3> </div> <div class="panel-body">'+message+'</div> </div></div> </div>');
                    scrollDown();
                },
                error: function() {
                    alert("there is an error");
                }
            })
        });

        $("input[name='message']").keypress(function(e) {
            if(e.keyCode == 13) {
                $("#send-btn").click();
            }

            return;
        })

        loadNewMessages();


    </script>
@endsection