(function(){
    window.guave = window.guave || {};
    window.guave.translatetool = new function(){
        var backdrop;
        var input;
        var content;
        var container;
        var button;
        var phrases = [];
        var config = window.translateToolConfig;

        function loadDialog(){
            button = document.createElement('a');
            button.setAttribute("style", "display: block; position: fixed; top: 10px; right: 10px; height: 30px; width: 30px;"
                +"border-radius: 5px; opacity: 0.7; background-color: #A4C92C; line-height: 30px; text-align: center; color: white");
            button.innerHTML = 'T';
            button.onclick = function(){
                displayDialog();
            }
            document.body.appendChild(button);

            backdrop = document.createElement('div');
            backdrop.setAttribute("style","display:none; position:fixed; top: 0; left: 0; bottom: 0; right: 0; z-index:100000; background-color: black; opacity: 0.5");
            backdrop.setAttribute('id', '_translatetool_backdrop');
            backdrop.onclick = hideDialog;
            document.body.appendChild(backdrop);

            container = document.createElement('div');
            container.setAttribute("style","display: none; position:fixed; top: 50%; left: 50%; z-index:100001;");
            container.setAttribute('id', '_translatetool_container');
            document.body.appendChild(container);

            content = document.createElement('div');
            content.setAttribute("style","position:relative; padding: 10px; background-color: white; margin-top: -60%; left: -50%;");
            content.setAttribute('id', '_translatetool_content');
            container.appendChild(content);
        }

        window.onload = function() {
            var anchors = document.getElementsByClassName('translatetool-phrase');
            for(var i = 0; i < anchors.length; i++) {
                var anchor = anchors[i];
                var key = anchor.getAttribute('data-key');
                phrases.push('<input type="text" style="width: 500px" name="'+ key +'" value="'+escapeHtml(anchor.innerHTML)+'">');
            }
            loadDialog();
        }

        function submitForm(){
            var form = document.getElementById('_translatetool_form');
            var params = {};
            for(i = 0; i < form.children.length; i++){
                var child = form.children[i];
                if(child.getAttribute('type') == 'text'){
                    params[child.getAttribute('name')] = child.value;
                }
            }

            document.getElementById('_translatetool_loader').style.display = 'inline';
            post(generateUrl('widget'), params, function(){
                console.log('Post Callback');
                document.getElementById('_translatetool_loader').style.display = 'none';
            });
            return false;
        }

        function post(url, params, callback){
            var paramString = JSON.stringify(params);
            var requestParams = serialize({
                language: config.language || 'de',
                keys : paramString
            });
            var http = new XMLHttpRequest();
            http.open("POST", url, true);
            http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            http.onload = callback;
            http.send(requestParams);
        }

        serialize = function(obj) {
            var str = [];
            for(var p in obj)
                if (obj.hasOwnProperty(p)) {
                    str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
                }
            return str.join("&");
        }

        function displayDialog(){
            var preHtml = '<form id="_translatetool_form" method="post" onsubmit="return window.guave.translatetool.submitForm()">';
            var postHtml = '<br><input type="submit" value="Speichern"><span id="_translatetool_loader" style="display: none">Loading...</span></form>';
            content.innerHTML = preHtml+phrases.join("<br>")+postHtml;
            backdrop.style.display = 'block';
            container.style.display = 'block';
        }

        function hideDialog(){
            backdrop.style.display = 'none';
            container.style.display = 'none';
        }

        function generateUrl(key){
            var URLs = {
                widget: "/widget/update"
            }
            return stripTrailingSlash(config.instanceUrl)+URLs[key]+'?apicall';
        }

        function stripTrailingSlash(str) {
            if(str.substr(-1) == '/') {
                return str.substr(0, str.length - 1);
            }
            return str;
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        this.submitForm = submitForm;
    }
})()