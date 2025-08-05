<?php

namespace Drupal\ai_blogpost\Plugin\AiAgent;

use Drupal\ai_agents\PluginBase\AiAgentBase;
use Drupal\ai_agents\PluginInterfaces\AiAgentInterface;

/**
 * Provides a Blog Generator AI Agent.
 *
 * @AiAgent(
 *   id = "blog_generator",
 *   label = @Translation("Blog Post Generator"),
 * )
 */
class BlogGenerator extends AiAgentBase implements AiAgentInterface {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'blog_generator';
  }

  /**
   * {@inheritdoc}
   */
  public function agentsNames(): array {
    return ['BlogBot'];
  }

  /**
   * {@inheritdoc}
   */
  public function agentsCapabilities(): array {
    return [
      $this->getId() => [
        'name' => $this->t('Blog Generator'),
        'description' => $this->t('Generates a blog post from a user-provided prompt.'),
        'input' => ['prompt' => 'string'],
        'output' => ['title' => 'string', 'body' => 'string'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function determineSolvability() {
    $task = $this->getTask();
    $comments = $task->getComments();

    if (!empty($comments['prompt'])) {
      return AiAgentInterface::JOB_SOLVABLE;
    }

    return AiAgentInterface::JOB_NOT_SOLVABLE;
  }

  /**
   * {@inheritdoc}
   */
  public function solve(): array {
    $prompt = $this->getTask()->getComments()['prompt'] ?? '';

    // Communicate with the LLM via the helper.
    $response = $this->agentHelper->runSubAgent('generate', [
      'BLOG_PROMPT' => $prompt,
    ]);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function answerQuestion(): array {
    // Optional: If your agent doesn't use this, return a default.
    return [
      'title' => 'Unable to generate',
      'body' => 'This agent cannot answer questions.',
    ];
  }
}
