import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchAssos } from '../actions/assos';
import { assoActions } from '../actions.js';

import AssoChildrenList from '../components/AssoChildrenList';

@connect(store => {
	console.log(store)
	return {
		assos: store.asso.data,
		fetching: store.asso.fetching,
		fetched: store.asso.fetched
	}
})
class AssosListScreen extends Component {

	componentWillMount() {
		console.log(assoActions.getAll())
		this.props.dispatch(assoActions.getAll('?all'))
	}

	render() {
		// Construction de l'arbre des assos
		let assosTree = [];
		if (this.props.fetched)
			this.props.assos.forEach(asso => {
				asso.children = [];
				if (asso.parent_id === null | asso.parent_id === 1) {
					// Ajout à la racine si BDE ou Poles
					assosTree.push(asso);
				} else {
					// Recherche du parent par recherche en largeur de l'arbre
					// TODO : cas où parent n'existe pas ?
					let nextParents = [];
					assosTree.forEach(parent => nextParents.push(parent));
					let parent;
					while(nextParents.length > 0) {
						parent = nextParents.pop();
						// On arrête si on a trouvé le parent
						if (parent.id === asso.parent_id)
							break;
						// Sinon on ajoute ses enfants à la liste de recherche 
						else
							nextParents = nextParents.concat(parent.children);
					}
					parent.children.push(asso);
				}
			})

		return (
			<div className="container">
				<h1 className="title">Liste des associations</h1>
				<span className={"loader large" + (this.props.fetching ? ' active' : '') }></span>

				<ul className="row list-row">
					{ assosTree.map(asso => (
						<AssoChildrenList key={asso.id} asso={asso} level={1} />
					)) }
					</ul>
			</div>
		);
	}
}

export default AssosListScreen;