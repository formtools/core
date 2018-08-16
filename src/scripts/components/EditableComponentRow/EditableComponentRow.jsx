const EditableComponentRow = ({ selected, name, folder, desc, version, disabled, toggleRow }) => (
	<tr>
		<td width="30" align="center">
			<input type="checkbox" checked={selected} onChange={() => toggleRow(folder)} disabled={disabled} />
		</td>
		<td>
			<b>{name}</b> <a href=""><b>{version}</b></a>
			<div>{desc}</div>
		</td>
	</tr>
);
EditableComponentRow.defaultProps = {
	disabled: false,
	toggleRow: () => {}
};

export default EditableComponentRow;