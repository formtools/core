import React from 'react';

const Dropdown = ({ data, selected, onChange }) => (
	<select value={selected} onChange={onChange}>
		{data.map((item) => (
			<option value={item.value} key={item.value}>{item.label}</option>
		))}
	</select>
);

export default Dropdown;
