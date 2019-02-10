import React from 'react';
import { connect } from 'react-redux';
import AspectRatio from 'react-aspect-ratio';
import { Button } from 'reactstrap';
import ReactMarkdown from 'react-markdown';

import actions from '../../redux/actions';

import ContactList from '../../components/Contact/List';
import Img from '../../components/Image';

@connect((store, props) => ({
	isAuthenticated: store.isFetched('user'),
	contacts: store.getData(['assos', props.asso.id, 'contacts']),
	contactsFailed: store.hasFailed(['assos', props.asso.id, 'contacts']),
	roles: store.getData(['assos', props.asso.id, 'roles']),
}))
class AssoHomeScreen extends React.Component {
	componentWillMount() {
		const {
			asso: { id },
		} = this.props;

		if (id) {
			this.loadAssosData(id);
		}
	}

	componentWillReceiveProps({ asso: { id } }) {
		const { asso } = this.props;

		if (asso.id !== id) {
			this.loadAssosData(id);
		}
	}

	getFollowButton(isFollowing, isMember) {
		const { follow, unfollow } = this.props;

		if (isFollowing && !isMember) {
			return (
				<Button className="m-1 btn btn-sm" color="danger" outline onClick={unfollow}>
					Ne plus suivre
				</Button>
			);
		}

		if (isMember) {
			return (
				<Button className="m-1 btn btn-sm" outline disabled>
					Suivre
				</Button>
			);
		}

		return (
			<Button className="m-1 btn btn-sm" color="primary" outline onClick={follow}>
				Suivre
			</Button>
		);
	}

	getMemberButton(isMember, isFollowing, isWaiting) {
		const { leave, join } = this.props;

		if (isMember) {
			if (isWaiting) {
				return (
					<Button
						className="m-1 btn btn-sm"
						color="warning"
						outline
						onClick={() => {
							leave(true);
						}}
					>
						En attente...
					</Button>
				);
			}

			return (
				<Button
					className="m-1 btn btn-sm"
					color="danger"
					outline
					onClick={() => {
						leave(false);
					}}
				>
					Quitter
				</Button>
			);
		}

		if (isFollowing) {
			return (
				<Button className="m-1 btn btn-sm" outline disabled>
					Rejoindre
				</Button>
			);
		}

		return (
			<Button className="m-1 btn btn-sm btn" color="primary" outline onClick={join}>
				Rejoindre
			</Button>
		);
	}

	loadAssosData(id) {
		const { dispatch } = this.props;

		dispatch(actions.assos(id).contacts.all());
	}

	render() {
		const {
			asso,
			isAuthenticated,
			userIsFollowing,
			userIsMember,
			userIsWaiting,
			contacts,
			contactsFailed,
		} = this.props;

		let color = `color-${asso.login}`;

		if (asso.parent) color += ` color-${asso.parent.login}`;

		return (
			<div className="container">
				{asso ? (
					<div className="row">
						<div className="col-md-2 mt-3 px-1 d-flex flex-md-column">
							<AspectRatio className="mb-2" ratio="1">
								<Img image={asso.image} style={{ width: '100%' }} />
							</AspectRatio>
							{isAuthenticated && this.getFollowButton(userIsFollowing, userIsMember)}
							{isAuthenticated &&
								this.getMemberButton(userIsMember, userIsFollowing, userIsWaiting)}
						</div>
						<div className="col-md-8">
							<h1 className={`title ${color}`}>
								{asso.shortname} <small className="text-muted h4">{asso.name}</small>
							</h1>
							<span className="mt-4">{asso.type && asso.type.description}</span>
							<ReactMarkdown className="my-3 text-justify" source={asso.description} />
							<ContactList className="mt-4" contacts={contacts} authorized={!contactsFailed} />
						</div>
					</div>
				) : null}
			</div>
		);
	}
}

export default AssoHomeScreen;
