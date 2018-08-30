import React, { Component } from 'react';
import PropTypes from 'prop-types';
import Dialog from '@material-ui/core/Dialog';
import DialogActions from '@material-ui/core/DialogActions';
import DialogContent from '@material-ui/core/DialogContent';
import DialogContentText from '@material-ui/core/DialogContentText';
import DialogTitle from '@material-ui/core/DialogTitle';
import Button from '@material-ui/core/Button';
import { withStyles } from '@material-ui/core/styles';
import blue from '@material-ui/core/colors/blue';
import CircularProgress from '@material-ui/core/CircularProgress';


const styles = {
    root: {
        textAlign: 'left'
    },
    avatar: {
        backgroundColor: blue[100],
        color: blue[600],
    },
    highlight: {
        backgroundColor: '#e0e9ff',
        border: '1px solid #0099cc',
        padding: 15,
        borderRadius: 4,
        color: '#0099cc',
        margin: '0 20px 5px 20px',
        flex: '1 0 auto'
    },
    nav: {
        width: '100%'
    }
};


class ScrollableDialog extends Component {
    state = {
        scroll: 'paper'
    };

    getContent () {
        const { isLoading, content } = this.props;
        if (isLoading) {
            return <CircularProgress />;
        }

        return content;
    }

    render () {
        const { open, onClose, title, desc } = this.props;
        return (
            <Dialog style={styles.root}
                open={open}
                onClose={onClose}
                scroll={this.state.scroll}
                maxWidth="md"
                aria-labelledby="scroll-dialog-title">

                <DialogTitle id="scroll-dialog-title">
                    {title}
                </DialogTitle>
                <DialogContent style={styles.highlight}>{desc}</DialogContent>
                <DialogContent>{this.getContent()}</DialogContent>
                <DialogActions>
                    <div style={styles.nav}>
                        <Button onClick={onClose} color="primary">&laquo; Prev</Button>
                        <Button onClick={onClose} color="primary">Next &raquo;</Button>
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


export default withStyles(styles)(ScrollableDialog);