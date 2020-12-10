import axios from "axios";
import { Post, PostComment, Paginator } from "../../types/models";
import Vue from "vue";

type ParentChild = { post: Post, comment: PostComment };

const state: Paginator = {
  current_page: 0,
  data: [],
  from: 0,
  last_page: 0,
  path: "",
  per_page: 0,
  to: 0,
  total: 0
};

const getters = {
  posts: state => state.data,
  exists: state => (id: number) => id in state.data,
  currentPage: state => state.current_page,
  totalPages: state => state.last_page
}

const mutations = {
  init(state, pagination) {
    state = Object.assign(state, pagination);
  },

  add(state, post: Post) {
    Vue.set(state.data, post.id!, post);
  },

  update(state, post: Post) {
    let { text, html, edit_count, editor, updated_at } = post; // update only these fields

    Vue.set(state.data, post.id!, {...state.data[post.id!], ...{text, html, edit_count, editor, updated_at}})
  },

  delete(state, post: Post) {
    post.deleted_at = new Date();
  },

  addComment(state, { post, comment}: ParentChild) {
    if (Array.isArray(post.comments)) {
      Vue.set(post, "comments", {});
    }

    Vue.set(post.comments, comment.id!, comment);

    post.comments_count! += 1;
  },

  updateComment(state, { post, comment}: ParentChild) {
    let { text, html } = comment; // update only text and html version

    Vue.set(post.comments, comment.id!, {...post.comments[comment.id], ...{text, html}});
  },

  deleteComment(state, comment: PostComment) {
    const post = state.data[comment.post_id!];

    Vue.delete(post.comments, comment.id!);
    post.comments_count! -= 1;
  },

  setComments(state, { post, comments }) {
    post.comments = comments;
    post.comments_count = comments.length;
  },

  restore(state, post: Post) {
    post.deleted_at = null;

    delete post.delete_reason;
    delete post.deleter_name;
  },

  vote(state, post: Post) {
    if (post.is_voted) {
      post.is_voted = false;
      post.score -= 1;
    }
    else {
      post.is_voted = true;
      post.score += 1;
    }
  },

  accept(state, post: Post) {
    if (post.is_accepted) {
      post.is_accepted = false;
    }
    else {
      let values: Post[] = Object.values(state.data);

      // user choose different option
      for (let item of values) {
        if (item.is_accepted) {
          item.is_accepted = false;
        }
      }

      post.is_accepted = true;
    }
  },

  subscribe(state, post: Post) {
    post.is_subscribed = !post.is_subscribed;
  },

  setVoters(state, { post, voters }) {
    Vue.set(post, 'voters', voters);
  }
}

const actions = {
  vote({ commit }, post: Post) {
    commit('vote', post);

    return axios.post(`/Forum/Post/Vote/${post.id}`)
      .then(result => commit('setVoters', { post, voters: result.data }))
      .catch(() => commit('vote', post));
  },

  accept({ commit, getters }, post: Post) {
    commit('accept', post);

    return axios.post(`/Forum/Post/Accept/${post.id}`).catch(() => commit('accept', post));
  },

  subscribe({ commit }, post: Post) {
    commit('subscribe', post);

    return axios.post(`/Forum/Post/Subscribe/${post.id}`).catch(() => commit('subscribe', post));
  },

  save({ commit, state, getters, rootState, rootGetters }, post: Post) {
    const topic = rootGetters['topics/topic'];
    const forum = rootGetters['forums/forum'];

    const payload = {
      text: post.text,
      subject: rootGetters['topics/topic'].subject,
      is_sticky: rootGetters['topics/topic'].is_sticky,
      assets: post.assets,
      tags: rootGetters['topics/topic'].tags!.map(o => o['name']),
      poll: rootState.poll.poll
    };

    return axios.post(`/Forum/${forum.slug}/Submit/${topic?.id || ''}/${post?.id || ''}`, payload).then(result => {
      commit(getters.exists(result.data.id) ? 'update' : 'add', result.data);

      return result;
    });
  },

  saveComment({ state, commit, getters }, comment: PostComment) {
    return axios.post(`/Forum/Comment/${comment.id || ''}`, comment).then(result => {
      const post = state.data[result.data.post_id!];

      commit(post.comments[result.data.id!] ? 'updateComment' : 'addComment', { post, comment: result.data });
    });
  },

  delete({ commit }, { post, reasonId }: { post: Post, reasonId: number | null }) {
    return axios.delete(`/Forum/Post/Delete/${post.id}`, { data: { reason: reasonId } }).then(() => commit('delete', post));
  },

  deleteComment({ commit }, comment: PostComment) {
    return axios.delete(`/Forum/Comment/Delete/${comment.id}`).then(() => commit('deleteComment', comment));
  },

  restore({ commit }, post: Post) {
    return axios.post(`/Forum/Post/Restore/${post.id}`).then(() => commit('restore', post));
  },

  merge({ commit, getters }, post: Post) {
    return axios.post(`/Forum/Post/Merge/${post.id}`).then(result => {
      commit('delete', post);
      commit('update', result.data);
    });
  },

  loadComments({ commit }, post: Post) {
    axios.get(`/Forum/Comment/Show/${post.id}`).then(result => {
      commit('setComments', { post, comments: result.data });
    })
  },

  upload({ commit }, { post, form }: { post: Post, form: FormData }) {
    return axios.post('/Forum/Upload', form)
      .then(response => {
        commit('addAttachment', { post: post, attachment: response.data })
      });
  },

  changePage({ commit, rootGetters }, page: number) {
    const topic = rootGetters['topics/topic'];
    const forum = rootGetters['forums/forum'];

    return axios.get(`/Forum/${forum.slug}/${topic.id}-${topic.slug}`, { params: { page }}).then(result => {
      commit('init', result.data);

      return result;
    });
  },

  loadVoters({ commit }, post: Post) {
    return axios.get(`/Forum/Post/Voters/${post.id}`).then(result => {
      commit('setVoters', { post, voters: result.data });
    });
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
};

