/*
Title:			Install Wizard Javascript
Author:			Sam Rayner - http://samrayner.com
Created:		2011-12-28
*/var CleanUp={done:function(){$("#cleanup-button").removeClass("active").addClass("done disabled").html("Files deleted")},click:function(a){a.preventDefault();$(this).removeClass("done").addClass("active").html("Deleting install files").off("click");$("#install-button").addClass("disabled").off("click");$.get("cleanup.php",CleanUp.done)},init:function(){$("#cleanup-button").click(CleanUp.click)}},Install={done:function(){var a=$("#install-button");Icons.removeAll(a);a.removeAttr("style").removeClass("active").addClass("done icon-ok-sign").html("Content added to Dropbox").click(Install.click)},updateProgress:function(){var a=$.ajax({url:"install_log.txt",cache:!1,complete:function(a){var b=a.responseText;if(!b)return window.setTimeout(Install.updateProgress,500);var c=b.split("\n"),d=c[2].replace(/\D/g,"");c.splice(0,4);var e=c.length;if(e>d)return!0;if(e>0){$("#install-button").html(c[e-1].replace(/^\t+/,""));var f=$("#install-button").outerWidth(),g=e/d*100;$("#install-button").css("background-position-x",Math.round(f*g/100)+"px")}window.setTimeout(Install.updateProgress,500)}})},run:function(a){window.setTimeout(Install.updateProgress,1e3);var b=$.ajax({url:"install_content.php",data:{host_root:$("host_root").val()},complete:Install.done})},click:function(a){a.preventDefault();var b=$(this);b.off("click");Icons.removeAll(b);b.removeClass("done").addClass("active icon-refresh").html("Preparing files...");Install.run()},init:function(){$("#install-button").click(Install.click)}};$(function(){Install.init();CleanUp.init()});