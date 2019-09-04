import React from 'react';
import Select from 'react-select';


const Dropdown = ({ data, selected, onChange }) => (
	<Select
		options={data}
		inputValue={selected}
		onChange={onChange}
		styles={{
			control: (provided, state) => {
				return {
					...provided,
					borderColor: state.isFocused ? '#89b290' : 'hsl(0,0%,80%)',
					boxShadow: 'none',
					'&:hover': {
						borderColor: state.isFocused ? '#89b290' : '#dddddd',
					}
				};
			},
			option: (provided, state) => {
				const newState = {
					...provided,
					'&:active': {
						backgroundColor: '#87c192'
					}
				};
				if (state.isSelected) {
					newState.backgroundColor = '#87c192';
				} else if (state.isFocused) {
					newState.backgroundColor = '#c9e5cf';
				}
				return newState;
			}
		}}
	/>
);

export default Dropdown;
