const wpispringClient = {
    emec: 0,
    listener: false,
    status_click: false,

    close: async (buttonId, event, user, key) => {
        document.getElementById("blockcentr").style.display = 'none'; //$(".blockcentr").slideToggle("2000");
        document.getElementById("blockall").remove(); //$(".blockall").remove();
        wpispringClient.status_click = false;
    },

    click: async (buttonId, event, user, key) => {
        const button = document.getElementById(buttonId);

        const span = document.createElement('span');
        span.setAttribute('id', buttonId+'-wait');

        span.innerHTML = 'Подождите...';

        document.getElementById(buttonId+'-content-0').setAttribute('style', 'display: none;');
        document.getElementById(buttonId+'-content-1').setAttribute('style', 'display: none;');

        button.appendChild(span);

        const data = new FormData;

        data.append('wpispring-button-event', event);
        data.append('wpispring-button-user', user);
        data.append('wpispring-button-key', key);

        const request = await fetch(
            '/wp-json/wpispring/v1/click',
            {
                method: 'POST',
                credentials: 'include',
                body: data
            }
        );

        if (request.ok)
        {
            const answer = await request.json();

            if (answer.code == 0) console.log('wpispringClient.click(): success.');
            else console.error('wpispringClient.click(): API error.');
        }
        else console.error('wpispringClient.click(): network error.');

        wpispringClient.status_click = false;

        button.removeChild(span);
        document.getElementById(buttonId+'-content-0').removeAttribute('style');
        document.getElementById("blockcentr").style.display = 'none';
        document.getElementById("blockall").remove();
    },

    sendnmo: async (event) => {

        send_nmo_span = document.getElementById('send-nmo-span');
        send_nmo_span.innerHTML = 'Подождите...';
        const data = new FormData;

        data.append('wpispring-button-event', event);

        const request = await fetch(
            '/wp-json/wpispring/v1/sendnmo',
            {
                method: 'POST',
                credentials: 'include',
                body: data
            }
        );

        if (request.ok)
        {
            const answer = await request.json();

            if (answer.code == 0) console.log('wpispringClient.sendnmo(): success.');
            else console.error('wpispringClient.sendnmo(): API error.');
        }
        else console.error('wpispringClient.sendnmo(): network error.');

        send_nmo_span.innerHTML = 'Разослать уведомления НМО';
    },

    checknmo: async (event, user) => {
        const data = new FormData;

        data.append('wpispring-button-event', event);
        data.append('wpispring-button-user', user);

        const request = await fetch(
            '/wp-json/wpispring/v1/checknmo',
            {
                method: 'POST',
                credentials: 'include',
                body: data
            }
        );

        if (request.ok)
        {
            const answer = await request.json();

            if (answer.code == 0) {
              console.log('wpispringClient.checknmo(): success.');

              if(answer.status == 1  && wpispringClient.status_click == false)
              {
                wpispringClient.status_click = true;

                document.getElementById("blockcentr").style.display = 'block';

                if(document.getElementById("blockall"))
                  document.getElementById("blockall").remove();
                else
                  document.getElementById("tytoknoall").innerHTML = '<div id="blockall" class="blockall"></div>';
              }
            }
            else
              console.error('wpispringClient.checknmo(): API error.');
        }
        else
          console.error('wpispringClient.checknmo(): network error.');
    }
};
