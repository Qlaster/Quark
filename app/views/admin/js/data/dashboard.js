

    //~ function loadPhones() 
    //~ {
//~ 
      //~ var xhrxxx = new XMLHttpRequest();
//~ 
      //~ xhrxxx.open('GET', '/', true);
//~ 
//~ 
      //~ xhrxxx.send();
//~ 
//~ 
      //~ xhrxxx.onreadystatechange = function() 
      //~ {
        //~ if (xhrxxx.readyState != 4) return;
//~ 
        //~ //button.innerHTML = 'Готово!';
//~ 
        //~ if (xhrxxx.status != 200) {
          //~ // обработать ошибку
          //~ alert(xhrxxx.status + ': ' + xhrxxx.statusText);
        //~ } else {
          //~ // вывести результат
          //~ alert(xhrxxx.responseText);
        //~ }
//~ 
      //~ }
//~ 
    //~ }


//~ loadPhones();


function GetBaseUrl()
{
	return document.getElementById('baseurl').href;
}



function GetChartsData()
{
	//Получаем текущую дату
	var now = new Date();
	
	//Список дат для загрузки
	var statistic = [];
	//Дата в формате ISO в строке
	var stringDate = '';
		
		
		//~ function loadDashData()
		//~ {
			//~ for (index = 1; index <= statistic.length; index++) 
			//~ {
				//~ //Если хоть в одном потоке он не готов  - выходим
				//~ if ( statistic[index].readyState != 4) return ;
			//~ }	
			//~ 
			//~ alert(statistic[1].responseText);
//~ 
		//~ }
		
	now.setDate(now.getDate()-10);
	
	for (var day = 1; day <= 10; day++)
	{
		
		//Вычетаем по одному дню из даты
		now.setDate(now.getDate()+1);
		//Переводим в формат ISO
		stringDate = now.toISOString().substring(0, 10);
		
		//~ alert(GetBaseUrl()+'/admin/dashboard/info?date='+stringDate);
	    statistic[stringDate] = new XMLHttpRequest();
	    //~ statistic[1].timeout = day;
	    
		statistic[stringDate].open('GET', GetBaseUrl()+'/admin/dashboard/info?date='+stringDate, true);
		statistic[stringDate].send();



		
		
		statistic[stringDate].onreadystatechange = 	function ()
		{
			for (var date in statistic) 
			{
				//Если хоть в одном потоке он не готов  - выходим
				if ( statistic[date].readyState != 4) return ;
			}
			
			
			
			//Данные для отрисовки графика посещения
			var lineWaveData = [];
			lineWaveData['labels'] = [];
			lineWaveData['page'] = [];
			lineWaveData['unique'] = [];
			
			var browserData = [];
			var deviceData = [];
			var OSData = [];
			
			//Все данные получены - можно построить массив данных
			for (var date in statistic) 
			{
				//Десериализуем элемент
				statistic[date] = JSON.parse(statistic[date].responseText);
				
				lineWaveData['labels'].push(date);
				if (statistic[date] != undefined && statistic[date].length != 0)
				{	
					if (statistic[date][date]['page'] 	== undefined) statistic[date][date]['page'] = 0;				
					if (statistic[date][date]['unique'] == undefined) statistic[date][date]['unique'] = 0;
					
					lineWaveData['page'].push(statistic[date][date]['page']);
					lineWaveData['unique'].push(statistic[date][date]['unique']);
					
					//Подготавливаем информацию о браузерах
					for (var browsername in statistic[date][date].browsername) 
					{
						if (browserData[browsername] == undefined) browserData[browsername] =0;
						browserData[browsername] += statistic[date][date].browsername[browsername];
					}
					
					//Подготавливаем информацию о устройствах
					for (var device in statistic[date][date].type) 
					{
						if (deviceData[device] == undefined) deviceData[device] =0;
						deviceData[device] += statistic[date][date].type[device];
					}
					
					//Подготавливаем информацию о операционных системах
					for (var OS in statistic[date][date].osname) 
					{
						if (OSData[OS] == undefined) OSData[OS] =0;
						OSData[OS] += statistic[date][date].osname[OS];
					}
				}
				else
				{
					lineWaveData['page'].push(0);
					lineWaveData['unique'].push(0);
				}
			}
			
			console.log(lineWaveData);
			
			//Отрисовываем
			PaintWaveGraph('lineChart', lineWaveData);
			
			PaintChart('polarChart', OSData);
			PaintPolar('deviceChart', deviceData);
			PaintChart('doughnutChart', browserData);
			
			//~ for (index = 1; index < statistic.length; ++index) 
			//~ {
				//~ //Если хоть в одном потоке он не готов  - выходим
				//~ if ( statistic[index].readyState != 4) return ;
			//~ }	
			
			
			//~ 
			//~ var json_encode;
			//~ //Все данные получены - можно построить массив данных
			//~ for (index = 1; index < statistic.length; ++index) 
			//~ {
				//~ //alert(statistic[index].responseText);
				//~ statistic[index] = JSON.parse(statistic[index].responseText);
				//~ 
				//~ //return statistic;
			//~ }
			

			//~ console.log(statistic);

		}
		
		
		
		//~ function()
			//~ {
		//~ 
			//~ // console.log(header);
			//~ if (statistic[1].readyState != 4) return;
//~ 
			//~ if (statistic[1].status != 200) 
			//~ {
			  //~ // обработать ошибку
			  //~ alert(statistic[1].status + ': ' + statistic[1].statusText);
			//~ } 
			//~ else 
			//~ {
			  //~ // вывести результат
			  //~ alert(statistic[1].responseText);
			//~ }		
			//~ 
			//~ };
	}
		
		
		
		
		
		
		
		
		
		
		
		
		return;
		//Создаем новый поток для загрузки
		var xxx = new XMLHttpRequest();
		
		//Формируем url запроса
		//~ statistic[day].open('GET', GetBaseUrl()+'/admin/dashboard/info?date='+stringDate, true);
		xxx.open('GET', '/', true);
		
		//Отправляем
		xxx.send();
		
		
		function load(xhr)	
		{
//			var xhr = statistic[day];
			//~ if (xxx.status == 200) alert('!');
			//~ if (xxx.readyState != 4) return;
			//console.log(statistic[day]);
			console.log(xxx);
			alert(xxx.responseText);
			//~ alert(statistic[day].responseText);
		}
		
		//Вешаем обработчик
		xxx.onreadystatechange = load();
		
		

		

		//Добавляем в массив
		//dayList.push(stringDate);
	
	


		

		 // (1)

		//~ xhr.onreadystatechange = function() { // (3)
		  //~ if (xhr.readyState != 4) return;
//~ 
		  //~ button.innerHTML = 'Готово!';
//~ 
		  //~ if (xhr.status != 200) {
			//~ alert(xhr.status + ': ' + xhr.statusText);
		  //~ } else {
			//~ alert(xhr.responseText);
		  //~ }
//~ 
		//~ }


	
	//console.log(dayList);
}







//document.getElementById('baseurl').getAttribute('data-info')



function PaintWaveGraph(id, data)
{
		
	
	    var lineData = 
	    {
			labels: ["January", "February", "March", "April", "May", "June", "July"],
			datasets: [
				{
					label: "Просмотренные страницы",
					fillColor: "rgba(220,220,220,0.5)",
					strokeColor: "rgba(220,220,220,1)",
					pointColor: "rgba(220,220,220,1)",
					pointStrokeColor: "#fff",
					pointHighlightFill: "#fff",
					pointHighlightStroke: "rgba(220,220,220,1)",
					data: [65, 59, 80, 81, 56, 55, 40]
				},
				{
					label: "Посетители",
					fillColor: "rgba(26,179,148,0.5)",
					strokeColor: "rgba(26,179,148,0.7)",
					pointColor: "rgba(26,179,148,1)",
					pointStrokeColor: "#fff",
					pointHighlightFill: "#fff",
					pointHighlightStroke: "rgba(26,179,148,1)",
					data: [28, 48, 40, 19, 86, 27, 90]
				}
			]
		};

		lineData.labels = data.labels;
		lineData.datasets[0].data = data.page;
		lineData.datasets[1].data = data.unique;

		var lineOptions = 
		{
			scaleShowGridLines: true,
			scaleGridLineColor: "rgba(0,0,0,.05)",
			scaleGridLineWidth: 1,
			bezierCurve: true,
			bezierCurveTension: 0.4,
			pointDot: true,
			pointDotRadius: 4,
			pointDotStrokeWidth: 1,
			pointHitDetectionRadius: 20,
			datasetStroke: true,
			datasetStrokeWidth: 2,
			datasetFill: true,
			responsive: true,
		};


		var ctx = document.getElementById(id).getContext("2d");
		var myNewChart = new Chart(ctx).Line(lineData, lineOptions);
}


function PaintPolar(id, data)
{
	//~ var polarData = 
	//~ [
        //~ {
            //~ value: 300,
            //~ color: "#a3e1d4",
            //~ highlight: "#1ab394",
            //~ label: "App"
        //~ },
        //~ {
            //~ value: 140,
            //~ color: "#dedede",
            //~ highlight: "#1ab394",
            //~ label: "Software"
        //~ },
        //~ {
            //~ value: 200,
            //~ color: "#b5b8cf",
            //~ highlight: "#1ab394",
            //~ label: "Laptop"
        //~ }
//~ 
    //~ ];

    var polarData = [];
	var index_color = 0;
	
    for (var info in data) 
    {
		var record = new Object();
		
		record.value = data[info];
		record.label = info;
		record.color = color_palette(index_color);
		record.highlight = record.color;
		
		polarData.push(record);
		
		index_color++;
	}
	
	
    var polarOptions = {
        scaleShowLabelBackdrop: true,
        scaleBackdropColor: "rgba(255,255,255,0.75)",
        scaleBeginAtZero: true,
        scaleBackdropPaddingY: 1,
        scaleBackdropPaddingX: 1,
        scaleShowLine: true,
        segmentShowStroke: true,
        segmentStrokeColor: "#fff",
        segmentStrokeWidth: 2,
        animationSteps: 30,
        animationEasing: "easeOutSine",
        animateRotate: true,
        animateScale: false,
        responsive: true,

    };

    var ctx = document.getElementById(id).getContext("2d");
    var myNewChart = new Chart(ctx).PolarArea(polarData, polarOptions);
}


function PaintChart(id, data)
{

	//~ var doughnutData = 
	//~ [
        //~ {
            //~ value: 300,
            //~ color: "#a3e1d4",
            //~ highlight: "#1ab394",
            //~ label: "App"
        //~ },
        //~ {
            //~ value: 50,
            //~ color: "#dedede",
            //~ highlight: "#1ab394",
            //~ label: "Software"
        //~ },
        //~ {
            //~ value: 100,
            //~ color: "#b5b8cf",
            //~ highlight: "#1ab394",
            //~ label: "Laptop"
        //~ },
        //~ {
            //~ value: 120,
            //~ color: "#DA7A9B",
            //~ highlight: "#1ab394",
            //~ label: "Laptop"
        //~ }
        //~ 
    //~ ];
    
    
    var doughnutData = [];
	var index_color = 0;
	
    for (var info in data) 
    {
		var record = new Object();
		
		record.value = data[info];
		record.label = info;
		record.color = color_palette(index_color);
		record.highlight = record.color;
		
		doughnutData.push(record);
		
		index_color++;
	}
    
    
//~ 
    var doughnutOptions = {
        segmentShowStroke: false,
        segmentStrokeColor: "#fff",
        segmentStrokeWidth: 2,
        percentageInnerCutout: 15, // This is 0 for Pie charts
        animationSteps: 30,
        animationEasing: "easeOutSine",
        animateRotate: true,
        animateScale: false,
        responsive: true,
    };
//~ 
//~ 
    var ctx = document.getElementById(id).getContext("2d");
    var myNewChart = new Chart(ctx).Doughnut(doughnutData, doughnutOptions);
	
}


function color_palette(id)
{
	var color = ["#9999FF", "#DA7A9B", '#6699FF', '#66CC99', '#CCFF66', "#dedede", "#b5b8cf"];
	return color[id];
}


$(function () 
{
	GetChartsData();
	
    

});




