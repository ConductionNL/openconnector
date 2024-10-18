export default class ReadonlyBaseClass {

	#data

	constructor(properties) {
		// Store the data
		const processedProperties = this.processData(properties)
		this.#data = { ...processedProperties }

		// Dynamically define getters for each property
		Object.keys(this.#data).forEach((key) => {
			Object.defineProperty(this, key, {
				get: () => this.#data[key],
				enumerable: true,
				configurable: false,
			})
		})

		// Freeze the instance to prevent adding new properties
		Object.freeze(this)
	}

	processData(properties) {
		return properties
	}

}
