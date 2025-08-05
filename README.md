# AI Blog Post Module for Drupal

## What it does
This Drupal module allows users to generate AI-powered blog posts directly from a form interface. It integrates with an AI plugin system to craft content based on a prompt.

## Problem it Solves
Writing blog content manually can be time-consuming. This tool enables editors to jump-start their content creation process with AI-generated drafts tailored to a prompt.

## Why I Built It This Way
- I followed Drupal's best practices by separating form logic, routing, and plugin functionality.
- Prompt configuration is stored in YAML for easy reuse and customization.
- The module is extensible, allowing other AI agents or prompt strategies to be added in the future.

## Files
- `AIBlogPost.php`: Defines the form users interact with.
- `BlogGenerator.php`: AI plugin that handles content generation.
- `blog_prompt.yml`: Contains the prompt configuration for AI.
- `ai_blogpost.routing.yml`: Sets up the form route.
- `ai_blogpost.info.yml`: Declares the module to Drupal.

## How to Use
1. Install the module via Drupal’s module manager.
2. Visit `/ai-blog-post` to access the form.
3. Enter topic information and submit to get AI-generated content.
4. Use AI and AI Agent and Ai provider module.
5. Use key module for provider integration with AI model

