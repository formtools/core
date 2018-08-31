import React, { Component } from 'react';
import PropTypes from 'prop-types';
import Dialog from '@material-ui/core/Dialog';
import DialogActions from '@material-ui/core/DialogActions';
import DialogContent from '@material-ui/core/DialogContent';
import DialogTitle from '@material-ui/core/DialogTitle';
import Button from '@material-ui/core/Button';
import CircularProgress from '@material-ui/core/CircularProgress';
import * as helpers from '../../core/helpers';
import styles from './ScrollableDialog.scss';


class ScrollableDialog extends Component {
    constructor (props) {
        super(props);
        this.onKeypress = this.onKeypress.bind(this);
    }

    state = {
        scroll: 'paper'
    };

    // N.B. This component is always rendered to allow the clean fade in and out, so this is misleading
    componentWillMount () {
        document.addEventListener('keydown', this.onKeypress);
    }

    componentWillUnmount () {
        document.removeEventListener('keydown', this.onKeypress);
    }

    onKeypress (e) {
        e = e || window.event;

        const { open, onPrevNext } = this.props;
        if (!open) {
            return;
        }

        if (e.keyCode === 39) {
            onPrevNext('next');
        } else if (e.keyCode === 37) {
            onPrevNext('prev');
        }
    }

    getContent () {
        const { isLoading, content } = this.props;

        if (isLoading) {
            return (
                <CircularProgress style={{ color: '#21aa1e' }} size={50} thickness={3} />
            );
        }

        return content;
    }

    getDesc () {
        if (!this.props.isLoading) {
            return (
                <DialogContent className={styles.componentDesc}><div dangerouslySetInnerHTML={{ __html: this.props.desc }} /></DialogContent>
            );
        }
    }

    render () {
        const { open, onClose, prevLinkEnabled, nextLinkEnabled, onPrevNext, isLoading, i18n } = this.props;

        return (
            <Dialog className={styles.dialog}
                open={open}
                classes={{ paper: styles.paper }}
                onClose={onClose}
                scroll={this.state.scroll}
                maxWidth="md"
                aria-labelledby="scroll-dialog-title">
                <DialogTitle id="scroll-dialog-title">{this.props.title}</DialogTitle>
                {this.getDesc()}
                <DialogContent classes={{ root: isLoading ? styles.contentRoot : null }}>{this.getContent()}</DialogContent>
                <DialogActions className={styles.buttonRow}>
                    <div className={styles.prevNextNav}>
                        <Button onClick={() => onPrevNext('prev')} color="primary" disabled={!prevLinkEnabled}>
                            <span>&laquo; Prev</span>
                        </Button>
                        <Button onClick={() => onPrevNext('next')} color="primary" disabled={!nextLinkEnabled}>
                            <span>Next &raquo;</span>
                        </Button>
                    </div>
                    <Button onClick={onClose} color="primary">Close</Button>
                </DialogActions>
            </Dialog>
        );
    }
}
ScrollableDialog.propTypes = {
    open: PropTypes.bool.isRequired,
    onClose: PropTypes.func.isRequired,
    isLoading: PropTypes.bool.isRequired,
    title: PropTypes.string.isRequired,
    content: PropTypes.element.isRequired,
    nextLinkLabel: PropTypes.string,
    prevLinkLabel: PropTypes.string,
    onPrevNextClick: PropTypes.func
};

export default ScrollableDialog;