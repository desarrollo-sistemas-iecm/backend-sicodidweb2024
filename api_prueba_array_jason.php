
<script>
	const votosDto = [
	  ['2', '728', 'red', 'p1_36.png'],
	  ['3', '710', 'blue', 'p1_36.png'],
	  ['4', 963, 'green', 'p1_36.png'],
	  ['5', 541, 'silver', 'p1_36.png'],
	  ['6', 622, 'yellow', 'p1_36.png'],
	  ['7', 361, 'orange', 'p1_36.png']
	];

	// Mapear el array a un nuevo array de objetos
	const votosJsonArray = votosDto.map(item => {
	  return {
		id: item[0],
		votes: item[1],
		color: item[2],
		image: item[3]
	  };
	});

	// Convertir el array de objetos a JSON
	const votosJsonString = JSON.stringify(votosJsonArray, null, 2);

	// Imprimir el JSON resultante
	console.log(votosJsonString);


	const jsonString = '{"subcategories":[{"id":"1_1","name":"Subcategoría 1.1"},{"id":"1_2","name":"Subcategoría 1.2"}]}';

	// Parsear el JSON a un objeto JavaScript
	const jsonObject = JSON.parse(jsonString);

	// Obtener el array de subcategorías
	const subcategoriesArray = jsonObject.subcategories;

	// Imprimir el array resultante
	console.log(subcategoriesArray);
</script>




